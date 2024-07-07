<?php
/*
Plugin Name: Insmed Connector
Description: Sync Reference Documants from an external SFTP server to the WordPress.
Version: 0.1
Author: Derek Brottlund
*/

require __DIR__ . '/vendor/autoload.php';

use phpseclib3\Net\SFTP;
use League\Csv\Reader;
use \CloudConvert\CloudConvert;
use \CloudConvert\Models\Job;
use \CloudConvert\Models\Task;
use GuzzleHttp\Client;



function sftp_file_sync() {
    $sftp = new SFTP('sftp.example.com');

    if (!$sftp->login('username', 'password')) {
        exit('Login Failed');
    }

    $remote_files = $sftp->nlist('/remote/path');
    $upload_dir = wp_upload_dir();
    $local_path = $upload_dir['path'];

    foreach ($remote_files as $file) {
        if ($file != '.' && $file != '..') {
            $remote_file = '/remote/path/' . $file;
            $local_file = $local_path . '/' . $file;

            $sftp->get($remote_file, $local_file);
        }
    }
}

function parse_csv_file($file_path, $delimiter = '|') {
    try {
        // Check if file exists
        if (!file_exists($file_path) || !is_readable($file_path)) {
            throw new Exception('File not found or not readable.');
        }

        // Create a CSV reader with the custom delimiter
        $csv = Reader::createFromPath($file_path, 'r');
        $csv->setDelimiter($delimiter);
        $csv->setHeaderOffset(0); // Use the first row as the header

        // Get the records
        $records = $csv->getRecords();

        // Process the records
        $data = [];
        foreach ($records as $record) {
            $data[] = $record;
        }

        return $data;

    } catch (Exception $e) {
        // Log error
        error_log('CSV Parsing Error: ' . $e->getMessage());
        return false;
    }
}

// Example usage
add_action('init', function() {
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['path'] . '/example.csv'; // Replace with your CSV file path

    $parsed_data = parse_csv_file($file_path); // Specify the pipe delimiter

    if ($parsed_data) {
        // Output parsed data for testing
        echo '<pre>' . print_r($parsed_data, true) . '</pre>';
    } else {
        echo 'Failed to parse CSV file.';
    }
});

add_action('init', 'sftp_file_sync');
