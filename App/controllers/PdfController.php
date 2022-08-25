<?php

namespace Controller;

use lib\CustomException;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfController extends \Engine\Controller
{
    public function listarAtendimentos($request, $response, $args)
    {
        try {
            $body = $request->getParsedBody();
            $elasticModel = $this->loadModel('Elastic');
            $dt = $elasticModel->getTime();
            $dt = strtotime($dt);
            $dt = date('d/m/Y H:i:s', $dt);
            $body['data'] = $dt;

            // Opções para o DomPDF para habilitar imagens remotas
            $options = new Options();
            $options->set('isRemoteEnabled', true);

            // Instantiate and use the DomPDF class
            $dompdf = new Dompdf($options);

            $dompdf->loadHtml($this->view->fetch('documents/lista-atendimento.html', $body));
            $dompdf->setPaper("A4");
            $dompdf->render();
            $dompdf->stream("Atendimentos.pdf", ["Attachment" => 0]);
        } catch (CustomException | Exception $e) {
            return $response->withJson($e->getCompleteExceptionMessage(), $e->getCode());
        }
    }
    public function listarAcolhimento($request, $response, $args)
    {
        try {
            $body = $request->getParsedBody();
            $elasticModel = $this->loadModel('Elastic');
            $dt = $elasticModel->getTime();
            $dt = strtotime($dt);
            $dt = date('d/m/Y H:i:s', $dt);
            $body['data'] = $dt;

            // Opções para o DomPDF para habilitar imagens remotas
            $options = new Options();
            $options->set('isRemoteEnabled', true);

            // Instantiate and use the DomPDF class
            $dompdf = new Dompdf($options);

            $dompdf->loadHtml($this->view->fetch('documents/lista-acolhimento.html', $body));
            $dompdf->setPaper("A4");
            $dompdf->render();
            $dompdf->stream("Acolhimentos.pdf", ["Attachment" => 0]);
        } catch (CustomException | Exception $e) {
            return $response->withJson($e->getCompleteExceptionMessage(), $e->getCode());
        }
    }
}
