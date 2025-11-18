<?php

class ApiResponse
{
  private $status;
  private $data;
  private $message;

  public function __construct()
  {
    $this->status = 'success';
    $this->data = [];
    $this->message = '';
  }

  public function success($data = [], $message = '')
  {
    $this->status = 'success';
    $this->data = $data;
    $this->message = $message;
    return $this;
  }

  public function error($message = '', $data = [])
  {
    $this->status = 'error';
    $this->data = $data;
    $this->message = $message;
    return $this;
  }

  public function setData($data)
  {
    $this->data = $data;
    return $this;
  }

  public function setMessage($message)
  {
    $this->message = $message;
    return $this;
  }

  public function send($httpCode = 200)
  {
    http_response_code($httpCode);
    header('Content-Type: application/json');

    $response = [
      'status' => $this->status,
      'timestamp' => time(),
    ];

    if (!empty($this->message)) {
      $response['message'] = $this->message;
    }

    if (!empty($this->data)) {
      $response['data'] = $this->data;
    }

    echo json_encode($response);
    exit;
  }

  public static function create()
  {
    return new self();
  }
}
