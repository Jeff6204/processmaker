<?php

namespace ProcessMaker\Http\Controllers\Api;

use ProcessMaker\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Storage;

use function PHPUnit\Framework\matches;

/**
 * LogsController
 * 
 * This controller gets latest Monolog file and searches for
 * a specific string. If the string is found, it will attempt
 * to parse the JSON in the 2nd parameter and return the results.
 * 
 * This is being used to get timing data into our benchmarking system
 * 
 */
class LogsController extends Controller
{
    private $results = [];
    private $searches = [];

    public function index(Request $request)
    {
        $this->searches = array_filter(
            explode(
                ',', 
                $request->input('search')
            )
        );

        if (count($this->searches) === 0) {
            throw new \Exception('No search');
        }

        $files = collect(scandir(storage_path('logs')));
        $files = $files->filter(fn($file) => fnmatch('processmaker-*.log', $file));
        $files = $files->sort();

        if (!$request->input('all')) {
            $files = collect($files->last());
        }

        $files = $files->map(fn($file) => storage_path('logs/' . $file));

        foreach ($files as $file) {
            $this->readFile($file);
        }
        
        return $this->results;
    }

    private function readFile($file)
    {
        $handle = fopen($file, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $this->searchLine($line);
            }
            fclose($handle);
        }
    }

    private function searchLine($line)
    {
        foreach($this->searches as $search) {
            if (str_contains($line, $search)) {
                $this->addResult($search, $line);
                break;
            }
        }
    }

    private function addResult($search, $line)
    {
        if (preg_match('/(\{.*\})/', $line, $matches)) {
            $json = json_decode($matches[1], true);
            if ($json) {
                $this->results[] = array_merge(['search' => $search], $json);
            }
        }
    }
}
