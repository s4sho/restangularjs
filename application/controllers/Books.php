<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . "/libraries/REST_Controller.php";

class Books extends REST_Controller 
{
    public function __construct() 
	{
		// It's not save to write like this. * means all
		header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $method = $_SERVER['REQUEST_METHOD'];
        if($method == "OPTIONS") {
            die();
        }
        parent::__construct();
		$this->load->model('books_model');
	}
	
	public function index_get()
	{
		$books = $this->books_model->get();
		
		if( ! is_null($books))
		{
			$this->response(array("response" => $books), 200);
		} else
		{
			$this->response(array("error" => "No books available"), 404);
		}
	}
	
	public function find_get($id)
	{
		if( ! $id)
		{
			$this->response(NULL, 400);
		}
		
		$book = $this->books_model->get($id);
		
		if( ! is_null($book))
		{
			$this->response(array("response" => $book), 200);
		} else
		{
			$this->response(array("error" => "This book is not available"), 404);
		}
	}
	
	public function index_post()
	{
		if( ! $this->post("book"))
		{
			$this->response(NULL, 400);
		}
		
		$bookId = $this->books_model->save($this->post("book"));
		
		if(! is_null($bookId))
		{
			$this->response(array("response" => $bookId), 200);
		} else
		{
			$this->response(array("error" => "There was an error."), 400);
		}
	}
	
	public function index_put($id)
	{
		echo "Inside of index_put";
		
		if( ! $this->put("book") || ! $id)
		{
			$this->response(NULL, 400);
		}
		
		$update = $this->books_model->update($id, $this->put("book"));
		
		if(! is_null($update))
		{
			$this->response(array("response" => "The book is edited"), 200);
		} else
		{
			$this->response(array("error" => "There was an error"), 400);
		}
	}
	
	public function index_delete($id)
	{
		if( ! $id)
		{
			$this->response(NULL, 400);
		}
		
		$delete = $this->books_model->delete($id);
		
		if( ! is_null($delete))
		{
			$this->response(array("response" => "Book deleted"), 200);
		} else
		{
			$this->response(array("error" => "There was an error"), 400);
		}
	}
	
}