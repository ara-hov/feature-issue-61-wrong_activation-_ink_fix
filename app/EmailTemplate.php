<?php

namespace App;

use Exception;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    public function parse($data, $template)
    {
        $parsed = preg_replace_callback('/{{(.*?)}}/', function ($matches) use ($data) {
            list($shortCode, $index) = $matches;

            if( isset($data[$index]) ) {
                return $data[$index];
            } else {
                throw new Exception("Shortcode {$shortCode} not found in template id {$this->id}", 1);
            }

        }, $template);
        return $parsed;
    }
}
