<?php 
namespace App\Traits;

trait HttpResponses{

    protected function Success($data, $message = null ,$code = 200){
        return response()->json(
            [
                "status"=> "You have successfully made this request",
                "message" => $message,
                "data"=> $data
            ], $code);
    }
    protected function error($data, $message = null, $code){
        return response()->json([
            "status"=> "An error has occurred ....",
            "message"=> $message,
            "data"=> $data
        ], $code);

    }

}
