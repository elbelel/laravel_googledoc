<?php

namespace App\Http\Controllers;

use App\Google;
use Illuminate\Http\Request;

class GoogleController extends Controller
{
    //


    public function __construct(Google $google, Request $request)
    {
        $this->client = $google->client();
        $this->drive = $google->drive($this->client);
    }

    public function handlePost(Request $request)
    {
        global $image_url;

        if ($request->session()->get('access_token')){
            $client=$this->client;
            $client->setAccessToken($request->session()->get('access_token'));

//getting document from drive that contain 'blog'
            $pageToken = NULL;
            $optParams = [
                'q'=>"name contains 'blog'",
                'spaces'=>"drive",
                'fields' =>"nextPageToken, files(id, name)",
                'pageToken'=>$pageToken

            ];

            $respon = $this->drive->files->listFiles($optParams)->getFiles();

            foreach ($respon as $respons) {

                //getting the content of the documents

                $file = new \Google_Service_Docs($client);

                $document = $respons['id'];

                $doc = $file->documents->get($document);

                $contents = $doc->getBody()->getContent();

                $datas = [];

                for ($i = 0; $i < count($contents); $i++) {
                    if ($contents[$i]->getParagraph() == null) {
                        continue;
                    }
                    $table = $contents[$i]->getParagraph()->getElements();

                    for ($j = 0; $j < count($table); $j++) {

                        if ($table[$j]->getTextRun() == null) {
                            goto image;
                        }
                        $cell = $table[$j]->getTextRun()->getContent();
                        array_push($datas, $cell);

                        image:
                        if ($table[$j]->getInlineObjectElement() == null) {
                            continue;
                        }
                        $image_id = $table[$j]->getInlineObjectElement()->getInlineObjectId();
                        $url = $doc->getInlineObjects();

                        $image_url2 = "<img " . "src" . "=" . $url[$image_id]->getInlineObjectProperties()->getEmbeddedObject()->getImageProperties()->contentUri . "  />";
                        array_push($datas, $image_url2);
                        $image_url = $url[$image_id]->getInlineObjectProperties()->getEmbeddedObject()->getImageProperties()->contentUri;

                    }
                }

                \DB::table('posts')->insert([
                'title' => $doc->getTitle(),
                'body' => implode("", $datas),
                'image' => $image_url
                ]);
            }
            return redirect('/home')->with('success','post has been created');

        }else{

            return redirect('/home')->with('error','you have not been authenticated');
        }
    }
}
