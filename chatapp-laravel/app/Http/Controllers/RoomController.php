<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Message;
use App\Models\MessageLike;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');
        $limit = (int) ($request->query('limit') ?? 50);
        $offset = (int) ($request->query('offset') ?? 0);

        $rooms = Room::where('user_id', $userId)
            ->where('delete_flag', false)
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(fn($room) => [
                'id' => $room->id,
                'user_id' => $room->user_id,
                'name' => $room->name,
                'is_completed' => $room->is_completed,
                'created_at' => $room->created_at?->toDateTimeString(),
                'updated_at' => $room->updated_at?->toDateTimeString(),
            ]);

        return ApiResponse::success($rooms, 'Rooms retrieved successfully');
    }

    public function store(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');
        $input = $request->json()->all();
        $name = trim((string) ($input['name'] ?? 'New Chat')) ?: 'New Chat';

        $room = Room::create([
            'user_id' => $userId,
            'name' => $name,
        ]);

        return ApiResponse::success([
            'id' => $room->id,
            'user_id' => $room->user_id,
            'name' => $room->name,
            'is_completed' => $room->is_completed,
            'created_at' => $room->created_at?->toDateTimeString(),
            'updated_at' => $room->updated_at?->toDateTimeString(),
        ], 'Room created successfully', 201);
    }

    public function show(Request $request, int $id)
    {
        $userId = (int) $request->attributes->get('userId');

        $room = Room::where('id', $id)
            ->where('delete_flag', false)
            ->first();

        if (!$room || $room->user_id !== $userId) {
            return ApiResponse::forbidden('You do not have access to this room');
        }

        $limit = (int) ($request->query('limit') ?? 50);
        $offset = (int) ($request->query('offset') ?? 0);

        $messages = $room->messages()
            ->limit($limit)
            ->offset($offset)
            ->orderBy('created_at')
            ->get();

        // Like count と liked_by_me をアタッチ
        $messageIds = $messages->pluck('id')->toArray();
        $likeMap = MessageLike::whereIn('message_id', $messageIds)
            ->get()
            ->groupBy('message_id');

        $userLikes = MessageLike::whereIn('message_id', $messageIds)
            ->where('user_id', $userId)
            ->pluck('message_id')
            ->toArray();

        $messagesData = $messages->map(function ($msg) use ($likeMap, $userLikes) {
            return [
                'id' => $msg->id,
                'room_id' => $msg->room_id,
                'user_id' => $msg->user_id ?? null,
                'is_ai' => $msg->sender === 'bot',
                'content' => $msg->text,
                'like_count' => isset($likeMap[$msg->id]) ? $likeMap[$msg->id]->count() : 0,
                'liked_by_me' => in_array($msg->id, $userLikes),
                'created_at' => $msg->created_at?->toDateTimeString(),
                'updated_at' => $msg->updated_at?->toDateTimeString(),
            ];
        });

        return ApiResponse::success([
            'room' => [
                'id' => $room->id,
                'user_id' => $room->user_id,
                'name' => $room->name,
                'is_completed' => $room->is_completed,
                'created_at' => $room->created_at?->toDateTimeString(),
                'updated_at' => $room->updated_at?->toDateTimeString(),
            ],
            'messages' => $messagesData,
        ], 'Room details retrieved successfully');
    }

    public function update(Request $request, int $id)
    {
        $userId = (int) $request->attributes->get('userId');
        $input = $request->json()->all();

        $room = Room::where('id', $id)
            ->where('delete_flag', false)
            ->first();

        if (!$room || $room->user_id !== $userId) {
            return ApiResponse::forbidden('You do not have access to this room');
        }

        $updateData = [];

        if (isset($input['name'])) {
            $updateData['name'] = trim((string) $input['name']);
        }

        if (isset($input['is_completed'])) {
            $updateData['is_completed'] = (bool) $input['is_completed'];
        }

        if (empty($updateData)) {
            return ApiResponse::error('No data to update', 400);
        }

        $room->update($updateData);

        return ApiResponse::success([
            'id' => $room->id,
            'user_id' => $room->user_id,
            'name' => $room->name,
            'is_completed' => $room->is_completed,
            'created_at' => $room->created_at?->toDateTimeString(),
            'updated_at' => $room->updated_at?->toDateTimeString(),
        ], 'Room updated successfully');
    }

    public function destroy(Request $request, int $id)
    {
        $userId = (int) $request->attributes->get('userId');

        $room = Room::where('id', $id)
            ->where('delete_flag', false)
            ->first();

        if (!$room || $room->user_id !== $userId) {
            return ApiResponse::forbidden('You do not have access to this room');
        }

        $room->update(['delete_flag' => true]);

        return ApiResponse::success(null, 'Room deleted successfully');
    }

    public function todayTopics(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');

        // 今日の日付を取得
        $today = now()->startOfDay();

        // 今日作成されたルームから最初のメッセージのテキストを取得
        $topics = Room::where('user_id', $userId)
            ->where('delete_flag', false)
            ->whereDate('created_at', $today)
            ->with(['messages' => function ($query) {
                $query->where('delete_flag', 0)
                    ->orderBy('created_at', 'asc')
                    ->limit(1);
            }])
            ->get()
            ->map(function ($room) {
                $firstMessage = $room->messages->first();
                return [
                    'room_id' => $room->id,
                    'topic' => $firstMessage ? $firstMessage->text : '',
                    'created_at' => $room->created_at?->toDateTimeString(),
                ];
            })
            ->filter(function ($item) {
                return !empty($item['topic']);
            })
            ->values();

        return ApiResponse::success($topics->toArray());
    }

    public function weeklyTopicsRanking(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');

        // この1週間の開始日時を計算
        $weekAgo = now()->subDays(6)->startOfDay();

        // この1週間に投稿されたユーザーの質問（ユーザーメッセージ）をすべて取得
        $userQuestions = Message::whereIn('room_id', function ($query) use ($userId) {
                $query->select('id')
                    ->from('rooms')
                    ->where('user_id', $userId)
                    ->where('delete_flag', false);
            })
            ->where('sender', 'user')  // ユーザーメッセージのみ
            ->where('delete_flag', 0)
            ->where('created_at', '>=', $weekAgo)
            ->select('text')
            ->get();

        // テーマ抽出用のストップワード
        $stopwords = [
            'の', 'て', 'で', 'ある', 'いる', 'ない', 'した', 'される', 'もの', 'こと',
            'ため', 'など', 'ように', 'のは', 'として', 'よう', 'そう', 'ここ',
            'あれ', 'これ', 'それ', 'どれ', 'あの', 'この', 'その', 'どの',
            'ん', 'よ', 'な', 'か', 'も', 'から', 'まで', 'より', 'と', 'や',
            'へ', 'に', 'を', 'は', 'が', 'ぐらい', 'ほど',
            'たり', 'だり', 'ながら', 'つつ', 'つつも', 'ずつ', 'ぶり', 'め',
            'あ', 'い', 'う', 'え', 'お', 'ああ', 'いい', 'うう', 'ええ', 'おお',
            // 時間表現
            '今日', '明日', '昨日', '今年', '去年', '来年', '今月', '先月', '来月',
            '今週', '先週', '来週', '今朝', '今夜', '午前', '午後', '昼', '夜',
            '最近', 'いつ', 'いつも', 'たまに', 'ときどき', '時々', 'よく', 'すぐ',
            // 疑問詞・代名詞
            'なに', '何', 'なぜ', 'どう', 'どこ', 'だれ', '誰', 'いくつ', 'いくら',
            'わたし', '私', 'あなた', '彼', '彼女', '自分', 'みんな', '皆',
            // 一般的な動詞・形容詞
            'です', 'ます', 'ました', 'でした', 'ある', 'なる', 'する', 'できる',
            'ください', '下さい', 'ほしい', '欲しい', 'いい', '良い', '悪い',
            'すごい', '凄い', 'やばい', 'ちょっと', 'けっこう', '結構', 'とても', '非常',
            // その他の助詞・接続詞
            'だから', 'しかし', 'でも', 'けど', 'けれど', 'ところ', 'ので', 'のに',
            'また', 'そして', 'それで', 'それから', 'そこで', 'たとえば', '例えば',
            'つまり', 'ちなみに', 'さらに', 'しかも', 'ただ', 'ただし', 'なお',
            'について', 'に関して', 'における', 'によって', 'にとって', 'として'
        ];

        // テーマを抽出
        $topics = [];

        foreach ($userQuestions as $msg) {
            $text = $msg->text;

            // 句点や改行で区切る
            $sentences = preg_split('/[。！？\n]+/u', $text);

            foreach ($sentences as $sentence) {
                $sentence = trim($sentence);
                if (empty($sentence)) {
                    continue;
                }

                // 空白、読点、ナカグロ、全角スペースで分割
                $chunks = preg_split('/[\s、，・　]+/u', $sentence, -1, PREG_SPLIT_NO_EMPTY);

                // 各チャンクから文末の助詞を除去し、必要なら助詞で追加分割
                $words = [];
                foreach ($chunks as $chunk) {
                    // 末尾の助詞を削る（複数の助詞が連続している場合も対応）
                    $clean = preg_replace('/(は|が|を|に|へ|で|と|の|も|や|より|から|まで|そう|ない|たり|だり|ずつ|ぶり|め|ぐらい|ほど|など)+$/u', '', $chunk);
                    // 助詞で区切れる場合は分割（開始文字として機能する助詞は除外）
                    $sub = preg_split('/(で|が|を)/u', $clean, -1, PREG_SPLIT_NO_EMPTY);
                    foreach ($sub as $s) {
                        $s = trim($s);
                        if (!empty($s)) {
                            $words[] = $s;
                        }
                    }
                }

                foreach ($words as $word) {
                    // UTF-8エンコーディングを確認
                    if (!mb_check_encoding($word, 'UTF-8')) {
                        continue;
                    }

                    // 「？」を含む単語を除外
                    if (strpos($word, '?') !== false || strpos($word, '？') !== false) {
                        continue;
                    }

                    // ひらがなのみの単語は除外
                    $hiraganaOnly = preg_match('/^[ぁ-ん]+$/u', $word);
                    
                    // ひらがなを含む場合は、末尾の助詞をもう一度確認して削除
                    if (preg_match('/[ぁ-ん]/u', $word)) {
                        // 末尾の助詞を削除（先頭の助詞削除は行わない）
                        $word = preg_replace('/(は|が|を|に|へ|で|と|や|の|も|より|から|まで|そう|ない|たり|だり|ずつ|ぶり|め|ぐらい|ほど|など)+$/u', '', $word);
                    }
                    
                    // 削除後に空または1文字になった場合はスキップ
                    if (mb_strlen($word) < 2) {
                        continue;
                    }

                    // 2文字以上で、ストップワードではない場合
                    if (mb_strlen($word) >= 2 && !in_array($word, $stopwords) && !$hiraganaOnly) {
                        // 数字のみは除外
                        if (!preg_match('/^[0-9]+$/', $word)) {
                            // 記号のみの単語も除外
                            if (preg_match('/[ぁ-んァ-ヶー一-龠a-zA-Z0-9]/', $word)) {
                                // 英数字を含む場合は大文字に統一
                                $normalizedWord = mb_strtoupper($word, 'UTF-8');
                                if (!isset($topics[$normalizedWord])) {
                                    $topics[$normalizedWord] = 0;
                                }
                                $topics[$normalizedWord]++;
                            }
                        }
                    }
                }
            }
        }

        // 出現頻度でソート（降順）
        arsort($topics);

        // 有効なテーマをフィルタリング
        $validTopics = [];
        foreach ($topics as $topic => $count) {
            if (strpos($topic, '?') === false &&
                strpos($topic, '？') === false &&
                mb_check_encoding($topic, 'UTF-8') &&
                preg_match('/[ぁ-んァ-ヶー一-龠a-zA-Z0-9]/', $topic) &&
                $count >= 1) {
                $validTopics[$topic] = $count;
            }
        }

        // ランキング形式に変換
        $ranking = [];
        $rank = 1;
        foreach ($validTopics as $theme => $count) {
            $ranking[] = [
                'rank' => $rank,
                'theme' => $theme,
                'count' => $count,
            ];
            $rank++;
        }

        return ApiResponse::success($ranking);
    }
}
