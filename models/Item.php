<?php
namespace app\models;

class Item
{
	public $summary = [];
	public $declaration = [];
	public $declarantForeign = [];
	public $declarant = [];
	public $declarantIp = [];
	public $producerForeign = [];
	public $producer = [];
	public $producerIp = [];
	public $products = [];
	public $standarts = [];
	public $experts = [];
	public $documents = [];
	public $laboratory;
	public $certifierName;
	public $attachments;
}