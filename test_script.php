<?php 
require 'vendor/autoload.php'; 
$app = require_once 'bootstrap/app.php'; 
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); 
$service = new \App\Services\GeminiImageEditService(); 
var_dump($service->editImage('test_shoe.jpg'));
