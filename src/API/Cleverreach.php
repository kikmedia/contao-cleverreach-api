<?php

namespace Alnv\ContaoCleverreachApi\API;


class Cleverreach {


    protected $strTokenType = null;
    protected $strToken = null;


    public function __construct() {

        $objCurl = curl_init();
        curl_setopt( $objCurl,CURLOPT_URL, \Config::get('cleverreachTokenUrl') );
        curl_setopt( $objCurl,CURLOPT_USERPWD, \Config::get('cleverreachClientId') . ":" . \Config::get('cleverreachClientSecret') );
        curl_setopt( $objCurl,CURLOPT_POSTFIELDS, [
            'grant_type' => 'client_credentials'
        ]);
        curl_setopt( $objCurl,CURLOPT_RETURNTRANSFER, true );
        $varResult = curl_exec( $objCurl );
        curl_close ( $objCurl );

        if ( $varResult ) {

            $objResponse = json_decode( $varResult, true );

            $this->strToken = $objResponse['access_token'];
            $this->strTokenType = $objResponse['token_type'];
        }
    }


    public function subscribe( $arrGroups, $arrSubscriber, $strFormId = '' ) {

        if ( !is_array( $arrGroups ) || empty( $arrGroups ) ) {

            \System::log( 'Cleverreach API: No subscriber groups defined', __METHOD__, TL_ERROR );

            return null;
        }

        $objRequest = new \Request();
        $objRequest->setHeader( 'Content-Type', 'application/json' );
        $objRequest->setHeader( 'Authorization', ucfirst( $this->strTokenType ) . ' ' . $this->strToken );

        foreach ( $arrGroups as $strGroupId ) {

            $objRequest->send('https://rest.cleverreach.com/v3/groups.json/'. $strGroupId .'/receivers', json_encode( $arrSubscriber, 512 ), 'POST' );

            if ( $objRequest->hasError() ) {

                \System::log( $objRequest->error, __METHOD__, TL_ERROR );
            }

            if ( $objRequest->response ) {

                $arrResponse = json_decode( $objRequest->response, true );
                \System::log( 'Cleverreach API: You have new subscriber', __METHOD__, TL_ACCESS );

                if ( $strFormId ) {

                    $objRequest->send('https://rest.cleverreach.com/v3/forms.json/'. $strFormId .'/send/activate', json_encode([
                        'email' => $arrResponse['email'],
                        'doidata' => [
                            "user_ip"    => $_SERVER["REMOTE_ADDR"],
                            "referer"    => $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"],
                            "user_agent" => $_SERVER["HTTP_USER_AGENT"]
                        ]
                    ], 512 ), 'POST' );
                }
            }
        }
    }


    public function getGroups() {

        $arrReturn = [];
        $objRequest = new \Request();
        $objRequest->setHeader( 'Content-Type', 'application/json' );
        $objRequest->setHeader( 'Authorization', ucfirst( $this->strTokenType ) . ' ' . $this->strToken );

        $objRequest->send('https://rest.cleverreach.com/v3/groups.json', '', 'GET' );

        if ( $objRequest->hasError() ) {

            \System::log( $objRequest->error, __METHOD__, TL_ERROR );
        }

        if ( $objRequest->response ) {

            $arrResponse = json_decode( $objRequest->response, true );

            if ( is_array( $arrResponse ) && !empty( $arrResponse ) ) {

                foreach ( $arrResponse as $arrGroup ) {

                    $arrReturn[] = [
                        'label' => $arrGroup['name'],
                        'value' => $arrGroup['id']
                    ];
                }
            }
        }

        return $arrReturn;
    }


    public function getForms() {

        $arrReturn = [];
        $objRequest = new \Request();
        $objRequest->setHeader( 'Content-Type', 'application/json' );
        $objRequest->setHeader( 'Authorization', ucfirst( $this->strTokenType ) . ' ' . $this->strToken );

        $objRequest->send('https://rest.cleverreach.com/v3/forms.json', '', 'GET' );

        if ( $objRequest->hasError() ) {

            \System::log( $objRequest->error, __METHOD__, TL_ERROR );
        }

        if ( $objRequest->response ) {

            $arrResponse = json_decode( $objRequest->response, true );

            if ( is_array( $arrResponse ) && !empty( $arrResponse ) ) {

                foreach ( $arrResponse as $arrForm ) {

                    $arrReturn[] = [
                        'label' => $arrForm['name'],
                        'value' => $arrForm['id']
                    ];
                }
            }
        }

        return $arrReturn;
    }
}