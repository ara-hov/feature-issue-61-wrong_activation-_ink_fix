<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\MailTemplate;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __sendMail($identifier, $to, $params)
    {
        $template = MailTemplate::getByIdentifier($identifier);

        $mail_subject = $template->subject;
        $mail_body = $template->body;
        $mail_wildcards = explode(',', $template->wildcards);
        $to = trim($to);
        $from = trim($template->email_from);
        if(empty($from))
            $from = Config::get('MAIL_USERNAME');


        $mail_wildcard_values = [];
        foreach($mail_wildcards as $value) {
            $value = str_replace(['[',']'],'', $value);
            $mail_wildcard_values[] = $params[$value];
        }

        $mail_body = str_replace($mail_wildcards, $mail_wildcard_values, $mail_body);
        $headers = "From: $from" . "\r\n" ;
        //$headers .= "CC: $cc";
        //echo $mail_body; die;

        try {
            //$from = env('MAIL_USERNAME');

            Mail::send('emails.default_template', ['content' => $mail_body], function ($m) use ($to, $from, $mail_subject) {
                $m->from($from, env('APP_NAME'));
                $m->to($to)->subject($mail_subject);
            });
        }catch (\Exception $e){
            //print $e->getMessage();
            $headers .= "Content-Type: text/html";
            mail($to, $mail_subject, $mail_body, $headers);
        }

        return true;
    }
	
	
	protected function __setSession($key, $value)
    {
        $request = Request();
        $request->session()->put([
            $key => $value,
        ]);
        return true;
    }

    protected function __getSession($key)
    {
        $request = Request();
        return $request->session()->get($key);
    }
}
