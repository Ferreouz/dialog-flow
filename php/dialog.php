<?php
require 'token-json.php';
//error_reporting(E_ALL);
//ini_set('display_errors', 'On');
require __DIR__.'/vendor/autoload.php';
use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\TextInput;
use Google\Cloud\Dialogflow\V2\QueryInput;

if(empty($json->queryText) || empty($json->sessionId)){
    echo json_encode(['erro'=> 'faltando campos']);
    exit;
}
detect_intent_texts($json->queryText, $json->sessionId );

function get_field_value($field) 
{
    $kind = $field->getKind();
    if ($kind == "string_value")
        return $field->getStringValue();
    else if ($kind == "number_value")
        return $field->getNumberValue();
    else if ($kind == "bool_value")
        return $field->getBoolValue();
    else if ($kind == "null_value")
        return $field->getNullValue();
    else if ($kind == "list_value") {
        $list_values = $field->getListValue()->getValues();
        $values = [];
        foreach($list_values as $list_value)
            $values[] = get_field_value($list_value);

        return $values;    
    }
    else if ($kind == "struct_value")
        return $field->getStructValue();
}
function detect_intent_texts(
     $text , $sessionId
    )
{
    $projectId = "XXX";
    $languageCode = 'pt-BR';
    // new session
    $test = array('credentials' => './vendor/cred.json');
    $sessionsClient = new SessionsClient($test);
    $session = $sessionsClient->sessionName($projectId, $sessionId ?: uniqid());

    // query for each string in array

        // create text input
        $textInput = new TextInput();
        $textInput->setText($text);
        $textInput->setLanguageCode($languageCode);

        // create query input
        $queryInput = new QueryInput();
        $queryInput->setText($textInput);

        // get response and relevant info
        $response = $sessionsClient->detectIntent($session, $queryInput);
        $queryResult = $response->getQueryResult();


        // $queryText = $queryResult->getQueryText();
        // $intent = $queryResult->getIntent();
        // $displayName = $intent->getDisplayName();
        // $confidence = $queryResult->getIntentDetectionConfidence();
        $fulfilmentText = $queryResult->getFulfillmentText();

        $retorno = [
            'intent'=> $fulfilmentText
        ];
        if($fulfilmentText === "What is the person?"){//name not provided
            $retorno['intent'] = "default";
        }else if(str_contains($fulfilmentText,"nome")){
            $full = explode("|", $fulfilmentText);
            $nome_completo = ucfirst(strtolower($full[1]));
            $full2 = explode(" ", $nome_completo);
            $nome = ucfirst(strtolower($full2[0]));
            $retorno['intent'] = "nome";
            $retorno['nome'] = $nome;
            $retorno['nome_completo'] = $nome_completo;
        }
        else if(str_contains($fulfilmentText,"cursos")){
            $retorno['intent'] = "cursos";
            $detectedEntities = $queryResult->getParameters()->getFields();
            $curso = "";
            foreach ($detectedEntities as $entityName => $entityValue) {
                if(get_field_value($entityValue)) $curso .= $entityName . "-";
            }

            if($curso) $retorno['curso'] = $curso;
        }


      echo json_encode([0=>$retorno]);

    $sessionsClient->close();
}
