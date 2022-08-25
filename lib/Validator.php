<?php

namespace lib;

use lib\CustomException;

class Validator
{
    /**
     * @param array $data - list with the values to be validated
     * @param array $rules - the rules to be applied to each list element
     *      The rules should be separated by |
     *      Example:
     *           $rules = [
     *              'email' => 'required|email',
     *              'age' => 'required|numeric',
     *              'expire_date' => 'date|after:tomorrow',
     *              'punctuation' => 'digits_between:1,10|not_in:4,5',
     *              'color' => 'string|in:red,green,blue',
     *              'phone' => 'size:8'
     *           ];
     *      Options:
     *          - required - field must exist in $data
     *          - email - field must be in mail format
     *          - numeric - field must be a number
     *          - accepted - The field under validation must be yes, on, 1, or true
     *          - after:date - The field under validation must be a value after a given date.
     *                    The dates will be passed into the strtotime PHP function
     *          - after_or_equal:date - Similar to after, but considering equal
     *          - before:date - Similar to previous 2 arguments
     *          - alpha - The field under validation must be entirely alphabetic characters.
     *          - alpha_numeric - The field under validation may have alpha-numeric characters
     *          - between:min,max - The field under validation must have a size between the given min
     *                    and max (equal included). Strings and arrays are evaluated based in size
     *          - boolean - The field under validation must be able to be cast as a boolean.
     *                      Accepted input are true, false, 1, 0, "1", and "0".
     *          - date - The field under validation must be a valid date according to
     *                   the strtotime PHP function
     *          - date_equals:date - The field under validation must be equal to the given date
     *                              The dates will be passed into the PHP strtotime function.
     *          - date_format:format - The field under validation must match the given format,
     *                                 function \DateTime::createFromFormat is used
     *          - digits:value - The field under validation must be numeric and must have an exact length of value.
     *          - digits_between:min,max - The field under validation must have a length between
     *                              the given min and max (equal included).
     *          - in:foo,bar - The field under validation must be included in the given list of values
     *          - not_in:foo,bar - The field under validation must not be included in the given list of values
     *          - integer - The field under validation must be an integer
     *          - string - The field under validation must be a string
     *          - max:value -  must be less than or equal a maximum value.
     *                      Strings and arrays are evaluated based in size
     *          - min:value -  must be higher than or equal a minimum value.
     *                      Strings and arrays are evaluated based in size
     *          - regex:pattern - The field under validation must match the given regular expression.
     *          - size:value - The field under validation must have a size matching the given value,
     *                    represented by the number of chars if integer or string and the count function for arrays
     * @return array $result - return if the data is valid, if negative case returns a message explaining why is invalid
     */
    public function validate($data, $rules)
    {
        if (empty($data)) {
            throw new CustomException('Parâmetros não fornecidos', 500);
        }

        foreach ($rules as $field => $field_rules) {
            // Set as null if field does not exist in data array
            if (isset($data[$field])) {
                $value = $data[$field];
            } else {
                $value = null;
            }

            $separetedRules = explode("|", $field_rules);
            foreach ($separetedRules as $rule) {
                // Does not validate subsequent rules if field is optional (and is not set) or nullable (and equal to null)
                if (($rule === 'optional' && !array_key_exists($field, $data)) || ($rule === 'nullable' && $value === null)) {
                    break;
                }

                $result = $this->validateField($field, $value, $rule, $data);
                if (!$result['valid']) {
                    throw new CustomException($result[INVALID_MESSAGE], 422);
                }
            }
        }
        // If code reached here, all $results are valid, so return the last one
        return $result;
    }

    public function validateField($field, $value, $rule, $data)
    {
        $to_return = true;

        if ($rule == 'email') {
            $to_return = $this->validateEmail($value);
        } elseif ($rule == 'required') {
            $to_return = $this->validateRequired($data, $field);
        } elseif ($rule == 'numeric') {
            $to_return = $this->validateNumeric($value, $field);
        } elseif ($rule == 'accepted') {
            $to_return = $this->validateAccepted($value, $field);
        } elseif ($rule == 'alpha') {
            $to_return = $this->validateAlphabetic($value, $field);
        } elseif ($rule == 'alpha_numeric') {
            $to_return = $this->validateAlphaNumeric($value, $field);
        } elseif ($rule == 'array') {
            $to_return = $this->validateArray($value, $field);
        } elseif ($rule == 'boolean') {
            $to_return = $this->validateBoolean($value, $field);
        } elseif ($rule == 'integer') {
            $to_return = $this->validateInteger($value, $field);
        } elseif ($rule == 'string') {
            $to_return = $this->validateString($value, $field);
        } elseif ($rule == 'date') {
            $to_return = $this->validateDate($value, $field);
        } elseif (strpos($rule, 'size') !== false) {
            $to_return = $this->validateSize($rule, $value, $field);
        } elseif (strpos($rule, 'regex') !== false) {
            $to_return = $this->validateRegex($rule, $value, $field);
        } elseif (strpos($rule, 'date_equals') !== false) {
            $to_return = $this->validateDateEquals($rule, $value, $field);
        } elseif (strpos($rule, 'only_date_format') !== false) {
            $to_return = $this->validateOnlyDateFormat($rule, $value, $field);
        } elseif (strpos($rule, 'date_format') !== false) {
            $to_return = $this->validateDateFormat($rule, $value, $field);
        } elseif (strpos($rule, 'date_age_minor') !== false) {
            $to_return = $this->validateAgeMinor($value, $field);
        } elseif (strpos($rule, 'after_or_equal') !== false) {
            $to_return = $this->validateAfterOrEqualDate($rule, $value, $field);
        } elseif (strpos($rule, 'after') !== false) {
            $to_return = $this->validateAfterDate($rule, $value, $field);
        } elseif (strpos($rule, 'before_or_equal') !== false) {
            $to_return = $this->validateBeforeOrEqualDate($rule, $value, $field);
        } elseif (strpos($rule, 'before') !== false) {
            $to_return = $this->validateBeforeDate($rule, $value, $field);
        } elseif (strpos($rule, 'max') !== false) {
            $to_return = $this->validateMax($rule, $value, $field);
        } elseif (strpos($rule, 'min') !== false) {
            $to_return = $this->validateMin($rule, $value, $field);
        } elseif (strpos($rule, 'digits_between') !== false) {
            $to_return = $this->validateDigitsBetween($rule, $value, $field);
        } elseif (strpos($rule, 'between') !== false) {
            $to_return = $this->validateBetween($rule, $value, $field);
        } elseif (strpos($rule, 'digits') !== false) {
            $to_return = $this->validateDigits($rule, $value, $field);
        } elseif (strpos($rule, 'not_in') !== false) {
            $to_return = $this->validateNotIn($rule, $value, $field);
        } elseif (strpos($rule, 'in') !== false) {
            $to_return = $this->validateIn($rule, $value, $field);
        }

        if ($to_return === true) {
            return array("valid" => true, "invalid_message" => "");
        } else {
            return $to_return;
        }
    }

    public function validateRegex($rule, $value, $field)
    {
        $pattern = explode(":", $rule)[1];
        if (!preg_match($pattern, $value)) {
            $errMessage = "Field " . $field . " does not match pattern";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateSize($rule, $value, $field)
    {
        $size = explode(":", $rule)[1];
        if (is_array($value)) {
            $valid = (count($value) == $size);
        } else {
            $valid = (strlen((string)$value) == $size);
        }

        if (!$valid) {
            $errMessage = "O campo " . $field . " deve ter o tamanho " . $size . ".";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateDigits($rule, $value, $field)
    {
        $size = explode(":", $rule)[1];
        if (!(is_numeric($value) && strlen((string)$value) == $size)) {
            $errMessage = "Field " . $field . " must contain only numbers and must have size " . $size;
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateMax($rule, $value, $field)
    {
        $max = explode(":", $rule)[1];
        if (!($value <= $max)) {
            $errMessage = "Field " . $field . " exceeds maximum allowed value";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateMin($rule, $value, $field)
    {
        $min = explode(":", $rule)[1];
        if (!($value >= $min)) {
            $errMessage = "Field " . $field . " smaller than minimum allowed value";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateDigitsBetween($rule, $value, $field)
    {
        $minMax = explode(":", $rule)[1];
        $split = explode(",", $minMax);
        $min = $split[0];
        $max = $split[1];
        $length = strlen((string)$value);

        if (!($length >= $min && $length <= $max)) {
            $errMessage = "O tamanho do campo " . $field .  " deve ser entre " . $min . " e " . $max . ".";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateBetween($rule, $value, $field)
    {
        $minMax = explode(":", $rule)[1];
        $split = explode(",", $minMax);
        $min = $split[0];
        $max = $split[1];

        if (!(strlen($value) >= $min && strlen($value) <= $max)) {
            $errMessage = "O número de caracteres do campo " . $field . " deve ser entre " . $min . " e " . $max . ".";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateBoolean($value, $field)
    {
        $valid = ($value === true || $value === false || $value === 1
        || $value === 0 || $value === '1' || $value === '0');
        if (!$valid) {
            $errMessage = "O campo " . $field . " precisa ser dado como verdadeiro / falso.";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateInteger($value, $field)
    {
        if (!is_int($value)) {
            $errMessage = "O campo " . $field . " precisa ser um número inteiro.";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateString($value, $field)
    {
        if (!is_string($value)) {
            $errMessage = "O campo " . $field . " precisa ser uma frase (string).";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateAccepted($value, $field)
    {
        if (!($value == 'yes' || $value == 'on' || $value == 1 || $value === true)) {
            $errMessage = "Field " . $field . " must be yes, on, 1 or true";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateNumeric($value, $field)
    {
        if (!(is_numeric($value))) {
            $errMessage = "O campo " . $field . " precisa ser um número.";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateEmail($value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errMessage = "Formato de email inválido.";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateRequired($data, $field)
    {
        if (!array_key_exists($field, $data)) {
            $errMessage = "O campo " . $field . " é obrigatório e precisa ser informado.";

            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateDate($value, $field)
    {
        if (!strtotime($value)) {
            $errMessage = "O campo " . $field . " precisa ser um tipo válido de data.";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateAgeMinor($value, $field)
    {
        // 31556926 is the amount of seconds in a year
        $age = floor((time() - strtotime($value)) / 31556926);
        if ($age >= 18) {
            $errMessage = "O campo " . $field . " informa usuário tem mais de 18 anos de idade.";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateDateFormat($rule, $value, $field)
    {
        $format = explode(":", $rule)[1];
        if (!(\DateTime::createFromFormat($format, $value)) && strlen($value) != 4) {
            $errMessage = "O campo " . $field . " não apresenta um formato válido de data.";

            return array("valid" => false, "invalid_message" => $errMessage);
        } elseif (strlen($value) == 4) { // Creates DATE with only year
            $value = \DateTime::createFromFormat("Y", $value);
        } elseif (time() < strtotime($value)) {
            $errMessage = "Não é possível inserir uma data superior à data atual.";

            return array("valid" => false, "invalid_message" => $errMessage);
        }

        return true;
    }

    public function validateOnlyDateFormat($rule, $value, $field)
    {
        $format = explode(":", $rule)[1];
        if (!(\DateTime::createFromFormat($format, $value)) && strlen($value) != 4) {
            $errMessage = "O campo " . $field . " não apresenta um formato válido de data.";

            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateArray($value, $field)
    {
        if (!is_array($value)) {
            $errMessage = "O campo " . $field . " precisa ser um vetor de dados (array).";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateAlphaNumeric($value, $field)
    {
        if (!ctype_alnum($value)) {
            $errMessage = "O campo " . $field . " deve conter apenas caracteres alfa-numéricos.";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateAlphabetic($value, $field)
    {
        $reg = '/^[A-Za-záàâãéèêíïóôõöúçñÁÀÂÃÉÈÍÏÓÔÕÖÚÇÑ ]+$/';
        if (!preg_match($reg, $value)) {
            $errMessage = "O campo " . $field . " deve conter apenas caracteres alfabéticos.";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }


    public function validateNotIn($rule, $value, $field)
    {
        $notAllowedValues = explode(":", $rule)[1];
        $notAllowedValues = explode(",", $notAllowedValues);

        if (in_array($value, $notAllowedValues)) {
            $errMessage = "O campo " . $field . " não está contido nos valores aceitos.";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateIn($rule, $value, $field)
    {
        $accepted_values = explode(":", $rule)[1];
        $accepted_values = explode(",", $accepted_values);

        if (!in_array($value, $accepted_values)) {
            $errMessage = "O valor do campo " . $field . " não é aceito.";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateDateEquals($rule, $value, $field)
    {
        $givenDate = explode(":", $rule)[1];
        $value = strtotime($value);
        $givenDate = strtotime($givenDate);

        if (!($value && $givenDate && $value == $givenDate)) {
            $errMessage = "Field " . $field . " value not equal given date";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateAfterDate($rule, $value, $field)
    {
        $givenDate = explode(":", $rule)[1];
        $givenDate = strtotime($givenDate);
        $value = strtotime($value);

        if (!($value && $givenDate && $value > $givenDate)) {
            $errMessage = "Field " . $field . " value not after given date";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateAfterOrEqualDate($rule, $value, $field)
    {
        $givenDate = explode(":", $rule)[1];
        $givenDate = strtotime($givenDate);
        $value = strtotime($value);
        if (! ($value && $givenDate && $value >= $givenDate)) {
            $errMessage = "Field " . $field . " value not after or equal given date";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateBeforeDate($rule, $value, $field)
    {
        $givenDate = explode(":", $rule)[1];
        $givenDate = strtotime($givenDate);
        $value = strtotime($value);

        if (!($value && $givenDate && $value < $givenDate)) {
            $errMessage = "Field " . $field . " value not before given date";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateBeforeOrEqualDate($rule, $value, $field)
    {
        $givenDate = explode(":", $rule)[1];
        $givenDate = strtotime($givenDate);
        $value = strtotime($value);

        if (!($value && $givenDate && $value <= $givenDate)) {
            $errMessage = "Field " . $field . " value not before or equal given date";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }
}
