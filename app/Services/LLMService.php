<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LLMService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = env('GROQ_API_KEY');
    }

    /**
     * Envoie une requête à Groq (Llama 3) avec un contexte et historique.
     */
    public function generateResponse(string $userPrompt, array $context = [], array $history = [])
    {
        if (empty($this->apiKey)) {
            Log::error("Clé API Groq manquante.");
            return "Erreur : La clé API Groq n'est pas configurée dans le fichier .env.";
        }

        // Préparation du System Prompt
        $systemInstructions = "Tu es StockManager, un expert en gestion de stock. 
        Tes réponses doivent être EXTRÊMEMENT CONCISES, DIRECTES ET FACTUELLES. 
        Réponds en français.
        
        IMPORTANT : 
        - Pour les actions, utilise TOUJOURS l'ID numérique du produit fourni dans le contexte.
        - Stock Virtuel : Tu as accès au 'stock_physique' (quantite), 'stock_reserve' et 'stock_disponible' (physique - réservé). 
        - Toujours baser tes conseils de vente et de réapprovisionnement sur le STOCK DISPONIBLE.
        - Si un utilisateur demande l'état du stock, précise bien s'il y a des unités réservées (ex: \"121 unités totales, dont 40 réservées. Disponible : 81\").
        - Pour les analyses de rentabilité (ex: fournisseur le plus rentable par région/canal), utilise les données 'regional_sales_data' fournies dans le contexte.
        
        FORMATAGE :
        1. Markdown simple.
        2. Action: [ACTION:CREATE_ORDER:{\"produit_id\": ID_NUMERIQUE, \"quantite\": NOMBRE}]
        3. Graphique: [CHART:{\"type\": \"bar\", \"title\": \"...\", \"data\": [...]}]";


        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE);
        
        // Construction du tableau de messages au format OpenAI
        $messages = [
            ['role' => 'system', 'content' => $systemInstructions . "\n\nCONTEXTE ACTUEL:\n" . $contextJson]
        ];

        // Ajout de l'historique (limité à l'essentiel)
        foreach ($history as $msg) {
            $role = ($msg['role'] === 'assistant') ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => $msg['content']];
        }

        // Ajout du prompt actuel
        $messages[] = ['role' => 'user', 'content' => $userPrompt];

        try {
            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, [
                'model' => 'llama-3.3-70b-versatile',
                'messages' => $messages,
                'temperature' => 0.2,
                'max_tokens' => 1024,
            ]);


            if ($response->failed()) {
                Log::error('Erreur API Groq: ' . $response->body());
                return "Désolé, je rencontre une difficulté technique avec Groq actuellement.";
            }

            $result = $response->json();
            return $result['choices'][0]['message']['content'] ?? "Je n'ai pas pu générer de réponse.";

        } catch (\Exception $e) {
            Log::error('Exception LLMService (Groq): ' . $e->getMessage());
            return "Une erreur est survenue lors de la communication avec Groq.";
        }
    }

}
