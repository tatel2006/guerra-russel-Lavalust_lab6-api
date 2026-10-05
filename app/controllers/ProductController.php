<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * This is the controller that actually satisfies Lab 6's core
 * requirement: GET/POST/PUT/DELETE for products, protected by JWT.
 * Completely separate from account management and from login/logout.
 */
class ProductController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->call->database();
        $this->call->library('api');
        $this->call->model('ProductModel');
    }

    // GET /api/products/list
    public function list()
    {
        $this->api->require_jwt();
        $this->api->respond(['data' => $this->ProductModel->all()]);
    }

    // POST /api/products/create
    public function create()
    {
        $this->api->require_jwt();
        $body = $this->api->body();

        if (empty($body['product_name'])) {
            $this->api->respond_error('product_name is required.', 422);
        }

        $data = [
            'product_name' => $body['product_name'],
            'description'  => $body['description'] ?? '',
            'price'        => $body['price'] ?? 0,
            'quantity'     => $body['quantity'] ?? 0,
        ];

        $id = $this->ProductModel->insert($data);
        $this->api->respond([
            'message' => 'Product created.',
            'data'    => $this->ProductModel->find($id),
        ], 201);
    }

    // PUT /api/products/update/{id}
    public function update($id)
    {
        $this->api->require_jwt();
        $existing = $this->ProductModel->find($id);

        if (!$existing) {
            $this->api->respond_error('Product not found.', 404);
        }

        $body = $this->api->body();
        $data = [
            'product_name' => $body['product_name'] ?? $existing['product_name'],
            'description'  => $body['description'] ?? $existing['description'],
            'price'        => $body['price'] ?? $existing['price'],
            'quantity'     => $body['quantity'] ?? $existing['quantity'],
        ];

        $this->ProductModel->update($id, $data);
        $this->api->respond([
            'message' => 'Product updated.',
            'data'    => $this->ProductModel->find($id),
        ]);
    }

    // DELETE /api/products/delete/{id}
    public function delete($id)
    {
        $this->api->require_jwt();
        $existing = $this->ProductModel->find($id);

        if (!$existing) {
            $this->api->respond_error('Product not found.', 404);
        }

        $this->ProductModel->delete($id);
        $this->api->respond(['message' => 'Product deleted.']);
    }
}