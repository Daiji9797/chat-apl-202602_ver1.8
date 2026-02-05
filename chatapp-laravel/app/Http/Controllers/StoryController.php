<?php

namespace App\Http\Controllers;

use App\Models\GoalNote;
use App\Models\Room;
use App\Support\ApiResponse;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    public function getRoomGoals(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');

        $goals = GoalNote::where('user_id', $userId)
            ->goalNotes()
            ->with('room:id,name')
            ->distinct()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($goal) => [
                'id' => $goal->id,
                'room_id' => $goal->room_id,
                'room_name' => $goal->room?->name,
                'note_text' => $goal->note_text,
                'created_at' => $goal->created_at,
            ]);

        return ApiResponse::success($goals, 'Room goals retrieved successfully');
    }

    public function index(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');
        $roomId = (int) ($request->query('roomId') ?? 0);
        $storyType = $request->query('storyType');

        $query = GoalNote::where('user_id', $userId)->storyNotes();

        if ($roomId > 0) {
            $query = $query->where('room_id', $roomId);
        }

        $today = now()->startOfDay();

        if ($storyType === 'future') {
            $query = $query->where('story_date', '>=', $today);
        } elseif ($storyType === 'past') {
            $query = $query->where('story_date', '<', $today);
        }

        $stories = $query->with('room:id,name')
            ->orderBy('story_date', 'asc')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($story) => [
                'id' => $story->id,
                'room_id' => $story->room_id,
                'room_name' => $story->room?->name,
                'note_text' => $story->note_text,
                'story_date' => is_string($story->story_date) ? $story->story_date : $story->story_date?->format('Y-m-d'),
                'image_comment' => $story->image_comment,
                'story_image' => $story->story_image,
                'created_at' => $story->created_at,
            ]);

        return ApiResponse::success($stories, 'Stories retrieved successfully');
    }

    public function store(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');
        $input = $request->json()->all();

        $noteText = trim((string) ($input['note_text'] ?? ''));
        $roomId = (int) ($input['roomId'] ?? 0);
        $storyDate = $input['story_date'] ?? null;
        $storyImage = $input['story_image'] ?? null;
        $imageComment = isset($input['image_comment']) ? trim((string) $input['image_comment']) : null;

        if ($noteText === '') {
            return ApiResponse::error('note_text is required', 400);
        }

        try {
            // roomId が null の場合、デフォルトルーム「未来Story」を使用・作成
            if ($roomId === 0) {
                $defaultRoom = Room::where('user_id', $userId)
                    ->where('name', '未来Story')
                    ->first();

                if (!$defaultRoom) {
                    $defaultRoom = Room::create([
                        'user_id' => $userId,
                        'name' => '未来Story',
                    ]);
                }

                $roomId = $defaultRoom->id;
            } else {
                $room = Room::where('id', $roomId)->where('user_id', $userId)->first();

                if (!$room) {
                    return ApiResponse::forbidden('You do not have access to this room');
                }
            }

            $story = GoalNote::create([
                'user_id' => $userId,
                'room_id' => $roomId,
                'note_text' => $noteText,
                'story_date' => $storyDate,
                'story_image' => $storyImage,
                'image_comment' => $imageComment,
                'created_at' => now()->toDateTimeString(),
            ]);

            return ApiResponse::success([
                'id' => $story->id,
                'created_at' => $story->created_at,
            ], 'Story created successfully', 201);

        } catch (\Exception $e) {
            \Log::error('Story creation error', ['error' => $e->getMessage()]);
            return ApiResponse::error('Failed to create story', 500);
        }
    }

    public function update(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');
        $input = $request->json()->all();

        $storyId = (int) ($input['storyId'] ?? 0);
        $noteText = isset($input['note_text']) ? trim((string) $input['note_text']) : null;
        $storyDate = $input['story_date'] ?? null;
        $imageComment = isset($input['image_comment']) ? trim((string) $input['image_comment']) : null;
        $storyImage = $input['story_image'] ?? null;

        if ($storyId <= 0) {
            return ApiResponse::error('storyId is required', 400);
        }

        $story = GoalNote::where('id', $storyId)->where('user_id', $userId)->first();

        if (!$story) {
            return ApiResponse::forbidden('You do not have access to this story');
        }

        $updateData = [];

        if ($noteText !== null) {
            $updateData['note_text'] = $noteText;
        }

        if ($storyDate !== null) {
            $updateData['story_date'] = $storyDate;
        }

        if ($imageComment !== null) {
            $updateData['image_comment'] = $imageComment;
        }

        if ($storyImage !== null) {
            $updateData['story_image'] = $storyImage;
        }

        if (empty($updateData)) {
            return ApiResponse::error('Nothing to update', 400);
        }

        $story->update($updateData);

        return ApiResponse::success([], 'Story updated successfully');
    }

    public function destroy(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');
        $input = $request->json()->all();

        $storyId = (int) ($input['storyId'] ?? $request->query('storyId') ?? 0);

        if ($storyId <= 0) {
            return ApiResponse::error('storyId is required', 400);
        }

        $story = GoalNote::where('id', $storyId)->where('user_id', $userId)->first();

        if (!$story) {
            return ApiResponse::forbidden('You do not have access to this story');
        }

        $story->delete();

        return ApiResponse::success([], 'Story deleted successfully');
    }

    public function generateImage(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');

        $input = $request->json()->all();
        $noteText = trim($input['note_text'] ?? '');
        $imageComment = trim($input['image_comment'] ?? '');
        $provider = trim($input['provider'] ?? 'openai');

        if (empty($noteText)) {
            return ApiResponse::error('note_text is required', 400);
        }

        // プロンプトを構築
        $prompt = $this->buildImagePrompt($noteText, $imageComment);

        try {
            if ($provider === 'openai') {
                $imageBase64 = $this->generateImageWithOpenAI($prompt);
            } else {
                return ApiResponse::error('Unsupported provider', 400);
            }

            return ApiResponse::success([
                'image_base64' => $imageBase64,
            ], 'Image generated successfully');

        } catch (\Exception $e) {
            \Log::error('Image generation error: ' . $e->getMessage());
            return ApiResponse::error('Failed to generate image: ' . $e->getMessage(), 500);
        }
    }

    private function buildImagePrompt($japaneseText, $imageComment = '')
    {
        // キーワードマッピング
        $keywords = [
            '成長' => 'personal growth, people achieving goals',
            '成功' => 'success, achievement, celebration',
            '達成' => 'accomplishment, reaching goals',
            '未来' => 'bright future, hope, optimism',
            '幸せ' => 'happiness, joy, smiling people',
            '家族' => 'family together, warm atmosphere',
            '仕事' => 'professional work environment, office',
            '健康' => 'healthy lifestyle, wellness, fitness',
            '学習' => 'learning, studying, education',
            '目標' => 'achieving goals, success moment'
        ];

        // ビジュアル要素を構築
        $visualElements = [];
        foreach ($keywords as $jpWord => $enDescription) {
            if (mb_strpos($japaneseText, $jpWord) !== false) {
                $visualElements[] = $enDescription;
            }
        }

        // デフォルトシーン
        if (empty($visualElements)) {
            $visualElements[] = 'person achieving personal goals';
        }

        // プロンプトを構築
        $promptParts = [];
        $promptParts[] = "Anime style illustration showing " . implode(', ', $visualElements);
        $promptParts[] = "warm lighting, bright and positive atmosphere";
        $promptParts[] = "Japanese anime art style";
        $promptParts[] = "smiling characters showing happiness and hope";
        $promptParts[] = "modern and clean composition";
        $promptParts[] = "colorful and vibrant illustration";

        if (!empty($imageComment)) {
            $promptParts[] = $imageComment;
        }

        $prompt = implode(', ', $promptParts);
        $prompt .= ". High quality anime illustration, detailed digital art, bright colors, inspirational mood, manga style.";

        // プロンプト長を制限
        if (strlen($prompt) > 900) {
            $prompt = substr($prompt, 0, 900);
        }

        return $prompt;
    }

    private function generateImageWithOpenAI($prompt)
    {
        $apiKey = env('OPENAI_API_KEY');
        if (empty($apiKey)) {
            throw new \Exception('OPENAI_API_KEY is not configured');
        }

        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->post('https://api.openai.com/v1/images/generations', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'dall-e-3',
                    'prompt' => $prompt,
                    'n' => 1,
                    'size' => '1024x1024',
                    'response_format' => 'b64_json',
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (isset($body['data'][0]['b64_json'])) {
                return $body['data'][0]['b64_json'];
            }

            throw new \Exception('No image data in response');

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            \Log::error('OpenAI API error: ' . $e->getMessage());
            if ($e->hasResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                throw new \Exception('OpenAI API error: ' . ($errorBody['error']['message'] ?? 'Unknown error'));
            }
            throw new \Exception('OpenAI API request failed: ' . $e->getMessage());
        }
    }
}
