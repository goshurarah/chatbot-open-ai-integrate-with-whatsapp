<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsappConversation;
use OpenAI;

class WhatsappConversationController extends Controller
{
    public function handleWhatsappChat(Request $request)
    {
        // Create the OpenAI Client using the factory method
        $openAIClient = OpenAI::client(env('OPENAI_API_KEY'));

        $body = $request->json()->all();
        $message = $body['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'] ?? null;
        $senderWaId = $body['entry'][0]['changes'][0]['value']['contacts'][0]['wa_id'] ?? null;

        if (!$message || !$senderWaId) {
            return response()->json(['error' => 'Invalid data'], 400);
        }

        // Fetch existing chat history or initialize new
        $existingChat = WhatsappConversation::where('wa_id', $senderWaId)->first();
        $messagesArray = $existingChat ? $existingChat->messages : [];
        $trimmedMessagesArray = array_slice($messagesArray, -20);

        // Append user's message
        $trimmedMessagesArray[] = ['role' => 'user', 'content' => $message];
        // dd($trimmedMessagesArray);

        // Send to OpenAI API
        try {
            $gptResponse = $openAIClient->chat()->create([
                'model' => 'gpt-4',
                'messages' => $trimmedMessagesArray,
            ]);

            $gptText = $gptResponse->choices[0]->message->content ?? null;
            // dd($gptText);
            if (!$gptText) {
                return response()->json(['error' => 'No response from OpenAI'], 500);
            }

            // Append GPT response
            $trimmedMessagesArray[] = ['role' => 'assistant', 'content' => $gptText];

            // Save chat history
            if ($existingChat) {
                $existingChat->update(['messages' => $trimmedMessagesArray]);
            } else {
                WhatsappConversation::create([
                    'wa_id' => $senderWaId,
                    'messages' => $trimmedMessagesArray,
                ]);
            }

            // Send response to WhatsApp
            // $this->sendWhatsAppMessage($senderWaId, $gptText);

            return response()->json(['success' => true, 'reply' => $gptText]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error processing OpenAI request: ' . $e->getMessage()], 500);
        }
    }

    // private function sendWhatsAppMessage($wa_id, $message)
    // {
    //     // Send message using WhatsApp API
    //     $response = \Http::withToken(env('WHATSAPP_ACCESS_TOKEN'))
    //         ->post('https://graph.facebook.com/v21.0/383990428139164/messages', [
    //             'messaging_product' => 'whatsapp',
    //             'to' => $wa_id,
    //             'type' => 'text',
    //             'text' => ['body' => $message],
    //         ]);

    //     if (!$response->successful()) {
    //         throw new \Exception('Failed to send WhatsApp message');
    //     }
    // }
}
