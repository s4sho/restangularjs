<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Books_model extends CI_Model {
    
    public function __construct()
    {
       parent::__construct();
    }
	
	public function get($id = NULL)
	{
		if( ! is_null($id))
		{
			$query = $this->db->select("*")->from("books")->where("id", $id)->get();
			if($query->num_rows() === 1)
			{
				return $query->row_array();
			}
			return NULL;
		}
		
		$query = $this->db->select("*")->from("books")->get();
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return NULL;
	}
	
	public function save($book)
	{
		$this->db->set(
			$this->_setBook($book)
		)
		->insert("books");
		
		if($this->db->affected_rows() === 1)
		{
			return $this->db->insert_id();
		}
		return NULL;
	}
	
	public function update($id, $book)
	{
		$this->db->set(
			$this->_setBook($book)
		)
		->where("id", $id)
		->update("books");
		
		if($this->db->affected_rows() === 1)
		{
			return TRUE;
		}
		return NULL;
	}
	
	public function delete($id)
	{
		$this->db->where("id",$id)->delete("books");
		
		if($this->db->affected_rows() === 1)
		{
			return TRUE;
		}
		return NULL;
	}
    
	public function _setBook($book)
	{
		return array(
			"title"		=>		$book["title"],
			"author"	=>		$book["author"],
			"isbn"		=>		$book["isbn"],
			"sinopsis"	=>		$book["sinopsis"],
		);
	}

}