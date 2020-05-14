<?php
namespace gotoAndPlay\Utils\MailChimp;

use gotoAndPlay\Utils\MailChimp as MailChimp;

class Batch {

    private $MailChimp;
    private $operations = [];
    private $batch_id;

    public function __construct(MailChimp $MailChimp, $batch_id = null) {
        $this->MailChimp = $MailChimp;
        $this->batch_id  = $batch_id;
    }

    public function delete($id, $method) {
        $this->queueOperation('DELETE', $id, $method);
    }

    public function get($id, $method, $args = []) {
        $this->queueOperation('GET', $id, $method, $args);
    }

    public function patch($id, $method, $args = []) {
        $this->queueOperation('PATCH', $id, $method, $args);
    }

    public function post($id, $method, $args = []) {
        $this->queueOperation('POST', $id, $method, $args);
    }

    public function put($id, $method, $args = []) {
        $this->queueOperation('PUT', $id, $method, $args);
    }

    public function execute($timeout = 10) {
        $req    = ['operations' => $this->operations];
        $result = $this->MailChimp->post('batches', $req, $timeout);
        if ($result && isset($result['id'])) {
            $this->batch_id = $result['id'];
        }

        return $result;
    }

    public function checkStatus($batch_id = null) {
        if ($batch_id === null && $this->batch_id) {
            $batch_id = $this->batch_id;
        }

        return $this->MailChimp->get('batches/' . $batch_id);
    }

    public function getOperations() {
        return $this->operations;
    }

    private function queueOperation($http_verb, $id, $method, $args = null) {
        $operation = [
            'operation_id' => $id,
            'method' => $http_verb,
            'path' => $method,
        ];

        if ($args) {
            if ($http_verb == 'GET') {
                $key             = 'params';
                $operation[$key] = $args;
            } else {
                $key             = 'body';
                $operation[$key] = json_encode($args);
            }
        }

        $this->operations[] = $operation;
    }

}
