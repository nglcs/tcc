<?php

namespace lib;

class PDOHelper extends MyPDO
{
    public static function displayDebugMessage($error, $envState)
    {
        // get css
        $css = '';
        $file = dirname(__FILE__) . '/debug.css';
        if (is_readable($file)) {
            $css = trim(file_get_contents($file));
        }

        // build the message
        $msg = '';
        $msg .= "\n" . '<style type="text/css">' . "\n" . $css . "\n" . '</style>';
        $msg .= "\n" . '<div class="debug">' . "\n\t" . '<h3>' . __METHOD__ . '</h3>';
        foreach ($error as $key => $value) {
            if ($key != 'Args' && $key != 'File') {
                $msg .= "\n\t" . '<label>' . $key . ':</label>' . $value;
            }
        }
        $msg .= "\n" . '</div>';
        // customize error handling based on environment:
        if ($envState == 'default') {  # Em produção
            echo $msg;
        } else {
            echo $msg;
        }
    }

    public static function gatherDebugSqlParms($sql, $bindings, $error, $backtrace, $envState)
    {
        // gather SQL params
        if (!empty($sql)) {
            $error['SQL statement'] = $sql;
        }
        $openPre = '<pre>';
        $closePre = '</pre>';
        if (!empty($bindings)) {
            $error['Bind Parameters'] = $openPre . print_r($bindings, true) . $closePre;
        }
        // show args if set
        if (!empty($backtrace[1]['args'])) {
            $error['Args'] = $openPre . print_r($backtrace[1]['args'], true) . $closePre;
        }
        // don't show variables if GLOBALS are set
        if (!empty($context) && empty($context['GLOBALS'])) {
            $error['Current Variables'] = $openPre . print_r($context, true) . $closePre;
        }
        $error['Environment'] = $envState;

        return $error;
    }

    public function preventUnsupported($sql)
    {
        // require a WHERE clause for deletes
        try {
            if (preg_match('/delete/i', $sql) && !preg_match('/where/i', $sql)) {
                throw new \PDOException('Missing WHERE clause for DELETE statement');
            }
        } catch (\PDOException $e) {
            $this->debug($e);
            return false;
        }
        // prevent unsupported actions
        try {
            if (!preg_match('/(select|describe|delete|insert|update|create|alter)+/i', $sql)) {
                throw new \PDOException('Unsupported SQL command');
            }
        } catch (\PDOException $e) {
            $this->debug($e);
            return false;
        }
        return true;
    }

    public function getTableFromQuery($sql)
    {
        try {
            $queryStructure = explode(' ', strtolower(preg_replace('!\s+!', ' ', $sql)));
            $searchesFrom = array_keys($queryStructure, 'from');
            $searchesDelete = array_keys($queryStructure, 'delete');
            $searches = array_merge($searchesFrom, $searchesDelete);

            foreach ($searches as $search) {
                if (isset($queryStructure[$search + 1])) {
                    return trim($queryStructure[$search + 1], '` ');
                }
            }
        } catch (\PDOException $e) {
            # It will not arrive here if the sql query is correct
            $this->debug($e);
            return false;
        }
    }

    public static function getMarkerBiding($value, $column, $bindings)
    {
        $marker = $boundValue = null;
        if (preg_match('/(:\w+|\?)/', $value, $matches)) {
            if (strpos(':', $matches[1]) !== false) {
                // look up the value (named parameters can be in any order)
                $marker = $matches[1];
                $boundValue = $bindings[$matches[1]];
            } else {
                // get the next value (question mark parameters are given in order)
                $marker = ':' . $column;
                $boundValue = array_shift($bindings);
            }
        // create the binding
        } else {
            $marker = ':' . $column;
            $boundValue = $value;
        }
        return array($marker, $boundValue);
    }

    public static function addMarkers($sql, $values, $bindings)
    {
        // add columns and parameter markers
        $markersBindings = array();
        $i = 0;
        foreach ($values as $column => $value) {
            // get the binding
            $bindingResult = PDOHelper::getMarkerBiding($value, $column, $bindings);
            $marker = $bindingResult[0];
            $boundValue = $bindingResult[1];

            // add the binding
            $markersBindings[$marker] = $boundValue;

            // add the SQL
            $sql .= ($i == 0) ? $column . ' = ' . $marker : ', ' . $column . ' = ' . $marker;
            $i++;
        }
        return array($sql, $markersBindings);
    }


    public static function buildInsertQuery($table, $values)
    {
        // Build the SQL:
        $sql = 'INSERT INTO ' . $table . ' (';
        // add column names
        $i = 0;

        foreach ($values as $column => $value) {
            $sql .= ($i == 0) ? $column : ', ' . $column;
            $i++;
        }
        return $sql . ') VALUES (';
    }

    public static function convertWhereToArray($where)
    {
        if (!is_array($where)) {
            $where = preg_split('/\b(where|and)\b/i', $where, null, PREG_SPLIT_NO_EMPTY);
            $where = array_map('trim', $where);
        }
        return $where;
    }

    public static function mountUpdateWhere($where, $finalBindings)
    {
        // loop through each condition
        foreach ($where as $i => $condition) {
            $marker = $boundValue = null;
            // split up condition into parts (column, operator, value)
            preg_match('/(\w+)\s*(=|<|>|!)+\s*(.+)/i', $condition, $parts);
            if (!empty($parts)) {
                // assign parts to variables
                list( , $column, , $value) = $parts;
                // get the binding
                if (preg_match('/(:\w+|\?)/', $value, $matches)) {
                    if (strpos(':', $matches[1]) !== false) {
                        // look up the value (named parameters can be in any order)
                        $marker = $matches[1];
                        $boundValue = $finalBindings[$matches[1]];
                    } else {
                        // get the next value (question mark parameters are given in order)
                        $marker = ':where_' . $column;
                        $boundValue = array_shift($finalBindings);
                    }
                // create the binding
                } else {
                    $marker = ':where_' . $column;
                    $boundValue = $value;
                }
                // add the binding
                $finalBindings[$marker] = $boundValue;
                // update the condition (replace value with marker)
                $where[$i] = substr_replace($condition, $marker, strpos($condition, $value));
            }
        }
        return array($where, $finalBindings);
    }

    public static function getIdValues($isPostgres)
    {
        $idVariable = "extra";
        $columnName = "Field";
        if ($isPostgres) {
            $columnName = "column_name";
            $idVariable = "identity_increment";
        }
        return array($idVariable,$columnName);
    }

    public static function compileColumnNames($info, $isPostgres)
    {
        $variablesNames = PDOHelper::getIdValues($isPostgres);
        $columnName = $variablesNames[1];

        // compile the column names
        $columns = array();
        foreach ($info as $item) {
            $columns[] = $item[$columnName];
        }
        return $columns;
    }

    public static function removeItems($columns, $values)
    {
        // remove items that don't match a column
        foreach ($values as $name => $value) {
            if (!in_array($name, $columns)) {
                unset($values[$name]);
            }
        }
        return $values;
    }

    public static function removeAiFields($info, $values, $isPostgres)
    {
        $variablesNames = PDOHelper::getIdValues($isPostgres);
        $idVariable = $variablesNames[0];
        $columnName = $variablesNames[1];

        $aiFields = array(); // auto-increment fields
        foreach ($info as $item) {
            if (isset($item[$idVariable]) && $item[$idVariable] != null) {
                $aiFields[] = $item[$columnName];
            }
        }
        // remove auto-increment fields
        if (!empty($aiFields)) {
            foreach ($aiFields as $item) {
                unset($values[$item]);
            }
        }
        return $values;
    }

    # Generate a string with paginate parameters, so it can be encrypted
    public function generatePaginateCode($table, $limit, $whereArray)
    {
        $commaSeparated = implode(",,", $whereArray);
        return $table . "::" . $limit . "::" . $commaSeparated;
    }

    public function recoverPaginateInfoFromCode($code)
    {
        $recover = explode("::", $code);
        $table = $recover[0];
        $limit = $recover[1];
        $whereArray = array_filter(explode(",,", $recover[2]));
        return array($table, $limit, $whereArray);
    }

    public function encryptSSL($plaintext, $cipherType, $key)
    {
        $ivlen = openssl_cipher_iv_length($cipherType);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt($plaintext, $cipherType, $key, $options = 0, $iv);
        return array("cipher_text" => $ciphertext, "iv" => $iv);
    }

    public function decryptSSL($cipherText, $cipherType, $key, $iv)
    {
        //store $cipher, $iv, and $tag for decryption later
        $originalPlaintext = openssl_decrypt($cipherText, $cipherType, $key, $options = 0, $iv);
        return $originalPlaintext;
    }
}
