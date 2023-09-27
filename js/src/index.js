// Import the packages we need
const dialogflow = require('@google-cloud/dialogflow');
require('dotenv').config();
const express = require('express');
const moment = require('moment')
moment.locale('pt-br');

// Creds
const CREDENTIALS = JSON.parse(process.env.CREDENTIALS);
const TOKEN = process.env.TOKEN;

const PROJECID = CREDENTIALS.project_id;
const CONFIGURATION = {
    credentials: {
        private_key: CREDENTIALS['private_key'],
        client_email: CREDENTIALS['client_email']
    }
}

// Create a new session
const sessionClient = new dialogflow.SessionsClient(CONFIGURATION);

// Detect intent method
const detectIntent = async (languageCode, queryText, sessionId) => {

    let sessionPath = sessionClient.projectAgentSessionPath(PROJECID, sessionId);

    // The text query request.
    let request = {
        session: sessionPath,
        queryInput: {
            text: {
                // The query to send to the dialogflow agent
                text: queryText,
                // The language used by the client (en-US)
                languageCode: languageCode,
            },
        },
    };

    // Send request and log result
    const responses = await sessionClient.detectIntent(request);
    // console.log(responses);
    const result = responses[0].queryResult;
    // console.log(result);

    return {
        response: result.fulfillmentText
    };
}
function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}


// Start the webapp
const webApp = express();

// Webapp settings
webApp.use(express.urlencoded({
    extended: true
}));
webApp.disable('x-powered-by');
webApp.use(express.json());

//auth
webApp.use( (req,res,next) => {
    try{

        let token = req.headers.authzao;
        if(token == TOKEN){
            next();
        }
        else {
            res.send(`Sorry :(`);
            return;
        }


    }catch(error){
        res.send(`Sorry :(`);
        return;
    }
}
);

const PORT = process.env.PORT || 3001;

webApp.get('/', (req, res) => {
    res.send(`Hello :)`);
});

webApp.post('/dialogflow', async (req, res) => {

    let languageCode = "pt-br";
    try{

        let queryText = req.body.queryText;
        let sessionId = req.body.sessionId;

        if(!queryText || !sessionId){
            res.send({"erro": "ta faltando campos"});
            return;
        }

        let responseData = await detectIntent(languageCode, queryText, sessionId);
        let resposta = responseData.response
        let retorno = {
            "intent":resposta,
            "nome": "",
            "nome_completo": ""
        } 
        if(resposta == "What is the person?") retorno.intent = "default";
        else if(resposta.includes("nome")){
            let nome_completo = capitalizeFirstLetter(resposta.split('|')[1]);
            let nome = capitalizeFirstLetter(nome_completo.split(" ")[0]);
            retorno.intent = "nome";
            retorno.nome = nome;
            retorno.nome_completo = nome_completo;
        }

        res.vary( "Accept-Encoding" )
        res.send([retorno]);

    }catch(error){
        res.send({"erro": "ta faltando campos"});
        console.log(error)
        return;
    }


});
// Start the server
webApp.listen(PORT, () => {
    console.log(`Server is up and running at ${PORT}`);
});
