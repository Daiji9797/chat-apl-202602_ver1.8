# Chat Application Backend (Laravel 11)

Laravel 12 で構築されたチャットアプリケーションのバックエンドです。React + Vite フロントエンドと連携する REST API を提供しています。

## 機能

### 認証・ユーザー管理
- **ユーザー認証** - JWT ベアラートークン方式による認証
  - ユーザー登録（メールアドレス・パスワード・ニックネーム）
  - ログイン・ログアウト
  - パスワード変更
  - アカウント削除
- **認可** - LegacyAuth ミドルウェアによる token 検証
- **CORS 対応** - フロントエンドからのリクエストに対応（preflight OPTIONS 対応）

### チャット・メッセージ機能
- **ルーム管理** - チャットルームの作成・更新・削除
- **メッセージ送受信** - テキストメッセージの送受信
- **メッセージ管理** - メッセージの削除機能
- **メッセージいいね** - ユーザーがメッセージに「いいね」を付与

### AI チャット
- **プロバイダ選択** - OpenAI API / Google Gemini API に対応
- **自動応答** - ユーザーのメッセージに対して AI が自動応答
- **コンテンツフィルタリング** - 不適切なコンテンツの自動検出・拒否

### 目標・ストーリー機能
- **目標達成メモ** - ルームごとに目標を設定・編集
- **未来 Story** - 過去→現在→未来のストーリーを時系列で管理
- **画像生成** - 目標テキストから DALL-E 3 で画像を自動生成

### ガチャゲーム機能
- **ガチャ実行** - ユーザーがポイントを消費してガチャを実行
- **段階的開示** - stage 1（閉じた状態）→ stage 2 → stage 3（完全に開いた状態）
- **ガチャ画像管理** - 各段階ごとの画像を管理
- **進捗永続化** - ユーザーのガチャ進捗状態をデータベースに保存

### その他機能
- **寄り添い画像（Stalker）** - ユーザーがアップロードした画像をマウスカーソルに追従
- **ルーム利用統計** - チャットルームの利用状況を集計
- **テーマランキング** - 直近 7 日間の質問から自動抽出したテーマを出現頻度でランキング表示
- **お問い合わせフォーム** - ユーザーからの問い合わせを受け付け、status 管理可能

## 必要な環境

- **PHP** 8.2 以上
- **Laravel** 12.49
- **MySQL** 5.7 以上
- **XAMPP** （Apache + PHP + MySQL）
- **Composer** 2.x 以上
- **OpenAI API キー** または **Google Gemini API キー**

## インストール手順

### 1. ファイルのコピー

```bash
C:\xampp\htdocs\chatapp-laravel  # このファイル
```

### 2. 依存パッケージのインストール

```bash
cd C:\xampp\htdocs\chatapp-laravel
composer install
```

### 3. 環境設定ファイルを作成

`.env.example` を `.env` にコピー：

```bash
cp .env.example .env
```

`.env` ファイルを編集：

```env
APP_NAME=ChatApp
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chatapp
DB_USERNAME=root
DB_PASSWORD=

OPENAI_API_KEY=sk-...
GEMINI_API_KEY=

JWT_SECRET=your-secret-key-here
JWT_ALGORITHM=HS256

DALL_E_API_KEY=${OPENAI_API_KEY}
```

### 4. アプリケーションキーを生成

```bash
php artisan key:generate
```

### 5. データベースマイグレーションを実行

```bash
php artisan migrate
```

### 6. ストレージシンボリックリンクを作成

```bash
php artisan storage:link
```

### 7. 開発サーバーを起動

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

サーバーは `http://localhost:8000` で起動します。

## プロジェクト構成

### バックエンド (Laravel)

```
chatapp-laravel/
├── app/                          # アプリケーション内のコード
│   ├── Http/                      # HTTP リクエスト・レスポンス関連
│   │   ├── Controllers/           # API コントローラー
│   │   │   ├── AuthController.php
│   │   │   ├── RoomController.php
│   │   │   ├── MessageController.php
│   │   │   ├── ChatController.php
│   │   │   ├── GoalController.php
│   │   │   ├── StoryController.php
│   │   │   ├── GachaController.php
│   │   │   ├── UserController.php
│   │   │   ├── ContactController.php
│   │   │   └── StatisticsController.php
│   │   ├── Middleware/            # 認証・CORS などのミドルウェア
│   │   │   ├── LegacyAuth.php     # JWT 認証
│   │   │   └── HandleCors.php     # CORS 処理
│   │   └── Requests/              # フォームリクエスト（バリデーション）
│   ├── Models/                    # Eloquent モデル
│   │   ├── User.php
│   │   ├── Room.php
│   │   ├── Message.php
│   │   ├── MessageLike.php
│   │   ├── GoalNote.php
│   │   ├── GachaStatus.php
│   │   ├── GachaImage.php
│   │   └── ContactMessage.php
│   ├── Providers/                 # サービスプロバイダー
│   └── Support/                   # ユーティリティ・ヘルパー関数
├── bootstrap/                     # アプリケーション起動処理
│   ├── app.php
│   ├── providers.php
│   └── cache/
├── config/                        # 設定ファイル
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── cors.php                   # CORS 設定（フロントエンドオリジン）
│   ├── database.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── queue.php
│   ├── services.php               # OpenAI, Gemini API 設定
│   └── session.php
├── database/                      # データベース関連
│   ├── migrations/                # テーブル定義ファイル
│   │   ├── create_users_table.php
│   │   ├── create_rooms_table.php
│   │   ├── create_messages_table.php
│   │   ├── create_goal_notes_table.php
│   │   ├── create_gacha_statuses_table.php
│   │   └── ...
│   ├── seeders/                   # テストデータ
│   └── factories/                 # モデルファクトリー
├── public/                        # 公開ディレクトリ（ドキュメントルート）
│   ├── index.php                  # エントリーポイント
│   └── robots.txt
├── resources/                     # リソース
│   ├── css/
│   ├── js/
│   └── views/                     # Blade テンプレート
├── routes/                        # ルート定義
│   ├── api.php                    # REST API ルート
│   ├── console.php
│   └── web.php
├── storage/                       # ストレージ（アップロードファイル・ログ）
│   ├── app/                       # ユーザーアップロードファイル保存先
│   │   └── public/                # 公開ファイル
│   │       ├── stalker-images/    # ユーザー寄り添い画像
│   │       └── story-images/      # ストーリー画像
│   ├── framework/                 # キャッシュ、セッション
│   └── logs/                      # エラーログ
├── tests/                         # テストコード
│   ├── Feature/
│   ├── Unit/
│   └── TestCase.php
├── vendor/                        # Composer パッケージ（自動生成）
├── .env                           # 環境変数設定
├── .env.example                   # テンプレート
├── artisan                        # Laravel コマンドラインツール
├── composer.json
├── composer.lock
├── phpunit.xml
└── README.md
```

### フロントエンド (React + Vite)

```
chatapp-react/
├── index.html                     # HTML エントリーポイント
├── vite.config.js                 # Vite ビルド設定
├── package.json                   # npm パッケージ定義
├── package-lock.json
├── public/                        # 静的アセット
│   ├── assets/
│   ├── terms.html                 # 利用規約
│   └── ...
├── src/
│   ├── App.jsx                    # ルートコンポーネント
│   ├── main.jsx                   # エントリーポイント
│   ├── App.css
│   ├── components/                # React コンポーネント
│   │   ├── Auth/
│   │   │   ├── LoginForm.jsx
│   │   │   ├── RegisterForm.jsx
│   │   │   └── ...
│   │   ├── Chat/
│   │   │   ├── ChatRoom.jsx
│   │   │   ├── MessageList.jsx
│   │   │   ├── MessageInput.jsx
│   │   │   └── ...
│   │   ├── Goal/
│   │   ├── Story/
│   │   ├── Gacha/
│   │   └── ...
│   ├── context/                   # React Context (状態管理)
│   │   └── AuthContext.jsx
│   ├── hooks/                     # カスタムフック
│   ├── services/                  # API 通信
│   │   ├── api.js                 # API クライアント
│   │   └── ...
│   └── styles/                    # CSS ファイル
├── scripts/
│   ├── deploy.js                  # デプロイスクリプト
│   └── watch-deploy.js            # ファイル監視スクリプト
└── dist/                          # ビルド出力（git 管理外）
    ├── index.html
    ├── assets/
    │   ├── index-xxxxx.js
    │   └── index-xxxxx.css
    └── ...
```

### さくらインターネット サーバ構成

さくらインターネット（silvercow67.sakura.ne.jp など）へのデプロイ時のディレクトリ構成：

```
~/（ホームディレクトリ）
├── www/                           # 公開ディレクトリ（ドキュメントルート）
│   └── chatapp-react/             # フロントエンド（React ビルド出力）
│       ├── index.html
│       ├── assets/
│       │   ├── index-xxxxx.js
│       │   └── index-xxxxx.css
│       ├── terms.html
│       └── ...
├── chatapp-laravel/               # バックエンド（Laravel）
│   ├── public/                    # Laravel ドキュメントルート
│   │   └── index.php
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── storage/
│   │   ├── app/
│   │   │   └── public/
│   │   │       ├── stalker-images/
│   │   │       └── story-images/
│   │   └── logs/
│   ├── .env                       # 本番環境設定
│   ├── vendor/
│   ├── composer.json
│   └── artisan
└── .htaccess                      # Apache リダイレクト設定
```

#### さくらインターネット アップロード手順

**1. フロントエンドのビルド**
```bash
cd chatapp-react
npm run build
```

**2. SCP でアップロード（フロントエンド）**
```bash
scp -r ./chatapp-react/dist/* ユーザー名@www3015.sakura.ne.jp:~/www/chatapp-react/
```

**3. SCP でアップロード（バックエンド）**
```bash
# Laravel 全体をアップロード（vendor は別途 composer install）
scp -r ./chatapp-laravel/* ユーザー名@www3015.sakura.ne.jp:~/chatapp-laravel/

# または、重要なファイルのみアップロード
scp -r ./chatapp-laravel/app ユーザー名@www3015.sakura.ne.jp:~/chatapp-laravel/
scp -r ./chatapp-laravel/config ユーザー名@www3015.sakura.ne.jp:~/chatapp-laravel/
scp -r ./chatapp-laravel/routes ユーザー名@www3015.sakura.ne.jp:~/chatapp-laravel/
scp -r ./chatapp-laravel/database ユーザー名@www3015.sakura.ne.jp:~/chatapp-laravel/
scp ./chatapp-laravel/.env ユーザー名@www3015.sakura.ne.jp:~/chatapp-laravel/
```

**4. SSH で接続し、バックエンド設定**
```bash
ssh -l ユーザー名 www3015.sakura.ne.jp

# バックエンドディレクトリへ
cd ~/chatapp-laravel

# Composer 依存パッケージをインストール
php -d memory_limit=512M /usr/bin/composer.phar install --no-dev

# データベースマイグレーション実行
php artisan migrate --force

# ストレージディレクトリのパーミッション設定
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

**5. Apache 設定（.htaccess）**
```apache
# ~/www/.htaccess （フロントエンド用）
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.html [L]
</IfModule>

# ~/chatapp-laravel/public/.htaccess （Laravel デフォルト）
# 通常は存在
```

## API エンドポイント

### 認証

| メソッド | エンドポイント | 説明 |
|---------|-------------|------|
| POST | `/api/register` | ユーザー登録 |
| POST | `/api/login` | ログイン |
| GET | `/api/user` | 現在のユーザー情報取得 |
| POST | `/api/change-password` | パスワード変更 |
| DELETE | `/api/user` | アカウント削除 |

### チャットルーム

| メソッド | エンドポイント | 説明 |
|---------|-------------|------|
| GET | `/api/rooms` | ルーム一覧取得 |
| POST | `/api/rooms` | ルーム作成 |
| GET | `/api/rooms/{id}` | ルーム詳細取得 |
| PUT | `/api/rooms/{id}` | ルーム更新 |
| DELETE | `/api/rooms/{id}` | ルーム削除 |

### メッセージ

| メソッド | エンドポイント | 説明 |
|---------|-------------|------|
| POST | `/api/messages` | メッセージ送信 |
| DELETE | `/api/messages/{id}` | メッセージ削除 |
| POST | `/api/message-likes` | メッセージいいね toggle |

### AI チャット

| メソッド | エンドポイント | 説明 |
|---------|-------------|------|
| POST | `/api/chat` | AI へのメッセージ送信 |

### 目標・ストーリー

| メソッド | エンドポイント | 説明 |
|---------|-------------|------|
| GET | `/api/goals` | 目標一覧取得 |
| POST | `/api/goals` | 目標作成 |
| PUT | `/api/goals` | 目標更新 |
| DELETE | `/api/goals` | 目標削除 |
| GET | `/api/story` | ストーリー一覧取得 |
| POST | `/api/story` | ストーリー作成 |
| PUT | `/api/story` | ストーリー更新 |
| GET | `/api/story/room-goals/{roomId}` | ルーム別目標一覧 |
| POST | `/api/story/generate-image` | 画像生成（DALL-E） |

### ガチャシステム

| メソッド | エンドポイント | 説明 |
|---------|-------------|------|
| POST | `/api/gacha` | ガチャ実行 |
| GET | `/api/gacha-status` | ガチャ状態取得 |
| GET | `/api/gacha-images` | ガチャ画像一覧取得 |

### その他

| メソッド | エンドポイント | 説明 |
|---------|-------------|------|
| POST | `/api/stalker-image` | 寄り添い画像アップロード |
| DELETE | `/api/stalker-image` | 寄り添い画像削除 |
| POST | `/api/contact` | お問い合わせ送信 |
| GET | `/api/today-topics` | 本日のテーマランキング |
| GET | `/api/weekly-topics-ranking` | 週間テーマランキング |

## データベーススキーマ

### テーブル一覧

| テーブル | 説明 |
|---------|------|
| `users` | ユーザー情報 |
| `rooms` | チャットルーム |
| `messages` | メッセージ |
| `message_likes` | メッセージいいね |
| `goal_notes` | 目標達成メモ |
| `gacha_statuses` | ガチャ進捗状態 |
| `gacha_images` | ガチャ画像 |
| `contact_messages` | お問い合わせ |

### テーブル詳細

#### users
```sql
CREATE TABLE users (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  points INT DEFAULT 0,
  stalker_image LONGTEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)
```

#### rooms
```sql
CREATE TABLE rooms (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  description LONGTEXT,
  completed BOOLEAN DEFAULT 0,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
```

#### messages
```sql
CREATE TABLE messages (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  room_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  message LONGTEXT NOT NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
```

#### message_likes
```sql
CREATE TABLE message_likes (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  message_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  UNIQUE KEY unique_likes (message_id, user_id),
  FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
```

#### goal_notes
```sql
CREATE TABLE goal_notes (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  room_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  goal_text LONGTEXT,
  story_text LONGTEXT,
  story_date DATE,
  image_comment LONGTEXT,
  story_image LONGTEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
```

#### gacha_statuses
```sql
CREATE TABLE gacha_statuses (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  gacha_id INT,
  cell INT,
  stage INT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
```

#### gacha_images
```sql
CREATE TABLE gacha_images (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  gacha_id INT,
  stage INT,
  filename VARCHAR(255),
  image_path TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)
```

#### contact_messages
```sql
CREATE TABLE contact_messages (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED,
  name VARCHAR(255),
  email VARCHAR(255),
  subject VARCHAR(255),
  message LONGTEXT,
  status ENUM('new', 'in_progress', 'resolved') DEFAULT 'new',
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)
```

## モデル一覧

### User
```php
// app/Models/User.php
- Fillable: name, email, password, points, stalker_image
- Relations: rooms(), messages(), messageAlikes(), goalNotes(), gachaStatuses(), contactMessages()
```

### Room
```php
// app/Models/Room.php
- Fillable: user_id, title, description, completed
- Relations: user(), messages(), goalNotes()
```

### Message
```php
// app/Models/Message.php
- Fillable: room_id, user_id, message
- Relations: room(), user(), likes()
```

### MessageLike
```php
// app/Models/MessageLike.php
- Fillable: message_id, user_id
- Relations: message(), user()
```

### GoalNote
```php
// app/Models/GoalNote.php
- Fillable: room_id, user_id, goal_text, story_text, story_date, image_comment, story_image
- Relations: room(), user()
```

### GachaStatus
```php
// app/Models/GachaStatus.php
- Fillable: user_id, gacha_id, cell, stage
- Relations: user()
```

### GachaImage
```php
// app/Models/GachaImage.php
- Fillable: gacha_id, stage, filename, image_path
```

### ContactMessage
```php
// app/Models/ContactMessage.php
- Fillable: user_id, name, email, subject, message, status
- Relations: user()
```

## コントローラー一覧

### AuthController
- `register()` - ユーザー登録
- `login()` - ログイン
- `user()` - 現在のユーザー取得
- `changePassword()` - パスワード変更
- `deleteAccount()` - アカウント削除

### RoomController
- `index()` - ルーム一覧
- `store()` - ルーム作成
- `show()` - ルーム詳細
- `update()` - ルーム更新
- `destroy()` - ルーム削除

### MessageController
- `store()` - メッセージ送信
- `destroy()` - メッセージ削除

### MessageLikeController
- `toggle()` - いいね toggle

### ChatController
- `chat()` - AI チャット

### GoalController
- `index()` - 目標一覧
- `store()` - 目標作成
- `update()` - 目標更新
- `destroy()` - 目標削除

### StoryController
- `index()` - ストーリー一覧
- `store()` - ストーリー作成
- `update()` - ストーリー更新
- `roomGoals()` - ルーム別目標
- `generateImage()` - 画像生成

### GachaController
- `executeGacha()` - ガチャ実行
- `getStatus()` - ガチャ状態取得
- `getImages()` - ガチャ画像取得

### UserController
- `uploadStalkerImage()` - 画像アップロード
- `deleteStalkerImage()` - 画像削除

### ContactController
- `store()` - お問い合わせ送信

### StatisticsController
- `todayTopics()` - 本日のテーマランキング
- `weeklyTopicsRanking()` - 週間テーマランキング

## ミドルウェア

### LegacyAuth
JWT ベアラートークンの検証。OPTIONS リクエスト（CORS プリフライト）を許可。

```php
// app/Http/Middleware/LegacyAuth.php
- Authorization ヘッダーから Bearer token を抽出
- JWT の署名・有効期限を検証
- OPTIONS リクエストは token 検証をスキップ
```

### HandleCors
CORS レスポンスヘッダーを追加。

```php
// app/Http/Middleware/HandleCors.php
- Access-Control-Allow-Origin: *
- Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
- Access-Control-Allow-Headers: Content-Type, Authorization
```

## トラブルシューティング

### 問題: "SQLSTATE[HY000] [2002] No such file or directory"
**原因**: MySQL が起動していない、または DB_HOST の設定が正しくない

**解決方法**:
1. XAMPP Control Panel で MySQL を起動
2. `.env` で `DB_HOST=127.0.0.1` を確認

### 問題: "Class AuthController not found"
**原因**: 名前空間が正しくない、またはファイルが存在しない

**解決方法**:
```bash
# PSR-4 オートロードを再生成
composer dump-autoload
```

### 問題: "Call to undefined function openai_chat()"
**原因**: OPENAI_API_KEY が設定されていない

**解決方法**:
1. `.env` に `OPENAI_API_KEY=sk-...` を設定
2. サーバーを再起動

### 問題: CORS エラー ("Access to XMLHttpRequest blocked by CORS policy")
**原因**: フロントエンドが異なるオリジンからアクセスしている

**解決方法**:
1. `config/cors.php` で `'supports_credentials' => true` を確認
2. `allowed_origins` に フロントエンドの URL を追加

### 問題: "The encrypted payload is invalid"
**原因**: APP_KEY が生成されていない、またはパスワードが正しく暗号化されていない

**解決方法**:
```bash
php artisan key:generate
```

## デプロイメント

### 本番環境への展開手順

1. **APP_ENV を production に変更**
```env
   APP_ENV=production
   APP_DEBUG=false
   ```

2. **強力な APP_KEY を生成**
```bash
   php artisan key:generate
   ```

3. **JWT_SECRET を複雑な値に変更**
```env
   JWT_SECRET=your-very-secure-random-string-here
   ```

4. **本番用最適化**
```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

5. **HTTPS を有効化**
   - SSL 証明書を取得（Let's Encrypt 推奨）
   - .htaccess で HTTP → HTTPS にリダイレクト

6. **ファイルパーミッションを設定**
```bash
   chmod -R 755 storage
   chmod -R 755 bootstrap/cache
   ```

7. **定期的なバックアップ**
   - MySQL データベースの自動バックアップ設定
   - storage/app の内容をバックアップ

### 本番構成の修正（必要最低限）

- `.env` を本番用に変更
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://<本番ドメイン>`
   - `DB_*` を本番DBに合わせる
   - `OPENAI_API_KEY` / `GEMINI_API_KEY` / `JWT_SECRET` を本番用に設定
- CORS 設定
   - `config/cors.php` の `allowed_origins` にフロントエンド本番URLを追加
- キャッシュ
   - `php artisan config:cache` / `route:cache` / `view:cache`

### アップロードする資産一覧（本番サーバー）

#### フロントエンド（React）
- `chatapp-react/dist/**`（ビルド成果物）
- `chatapp-react/public/terms.html`（必要なら dist に含めて配布）

#### バックエンド（Laravel）
- `chatapp-laravel/app/**`
- `chatapp-laravel/config/**`
- `chatapp-laravel/routes/**`
- `chatapp-laravel/database/**`
- `chatapp-laravel/public/**`
- `chatapp-laravel/resources/**`（Blade 変更がある場合）
- `chatapp-laravel/bootstrap/**`
- `chatapp-laravel/artisan`
- `chatapp-laravel/composer.json` / `composer.lock`
- `chatapp-laravel/.env`（本番用）

#### サーバー側で生成・保持されるもの
- `chatapp-laravel/vendor/**`（本番サーバーで `composer install --no-dev` で生成）
- `chatapp-laravel/storage/**`（ログ/キャッシュ/アップロード）

### さくらインターネット構築時の .htaccess

#### フロントエンド（~/www/.htaccess）
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # /api/* を Laravel にリダイレクト
    RewriteCond %{REQUEST_URI} ^/api/
    RewriteRule ^api/(.*)$ /chatapp-laravel/index.php [L,QSA]

    # 静的ファイル（存在するファイル/ディレクトリ）
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]

    # chatapp-react/index.html へのSPAルーティング
    RewriteCond %{REQUEST_URI} ^/chatapp-react/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ /chatapp-react/index.html [L]
</IfModule>
```

#### バックエンド（~/chatapp-laravel/public/.htaccess）
Laravel 既定の .htaccess を使用（変更不要）。

### さくらインターネット構築時の link 作成

```bash
cd ~/www
ln -s ~/chatapp-laravel/public chatapp-laravel
```

### GitHub に保存する資産一覧

#### 追跡（コミット対象）
- フロントエンド:
   `chatapp-react/src/**`,
   `chatapp-react/public/**`,
   `chatapp-react/package.json`,
   `chatapp-react/vite.config.js`
- バックエンド: 
   `chatapp-laravel/app/**`,
   `chatapp-laravel/config/**`,
   `chatapp-laravel/routes/**`,
   `chatapp-laravel/database/**`,
   `chatapp-laravel/resources/**`,
   `chatapp-laravel/public/**`,
   `chatapp-laravel/composer.json`,
   `chatapp-laravel/composer.lock`

#### 非追跡（コミットしない）
- `chatapp-react/dist/**`（ビルド成果物）
- `chatapp-laravel/vendor/**`
- `chatapp-laravel/.env`
- `chatapp-laravel/storage/**`

## パフォーマンス最適化

### データベース最適化
- `message.created_at` にインデックスを作成（クエリが遅い場合）
- `goal_notes.room_id` にインデックスを作成

```sql
CREATE INDEX idx_messages_created ON messages(created_at);
CREATE INDEX idx_goal_notes_room ON goal_notes(room_id);
```

### API レスポンス キャッシング
- `/api/gacha-images` はスタティック画像なのでキャッシング可能
- `/api/weekly-topics-ranking` は 1 時間ごとにキャッシュ

```php
// Example caching in controller
return Cache::remember('gacha-images', 3600, function () {
    return GachaImage::all();
});
```

### Laravelのインストール方法
- 最新の Composer 公式手順
- ① インストーラをダウンロード
```bash
   php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
   ```
- ② 最新のハッシュ値を取得
```bash
   php -r "copy('https://composer.github.io/installer.sig', 'installer.sig');"
   ```
- ③ ハッシュを検証
```bash
   php -r "if (hash_file('sha384', 'composer-setup.php') === trim(file_get_contents('installer.sig'))) { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
   ```
- ④ インストール
```bash
   php composer-setup.php
   ```
- ⑤composer コマンドとして使えるようにする（任意）
```bash
   echo 'alias composer="php ~/composer.phar"' >> ~/.bashrc
   source ~/.bashrc
- ⑥バージョン確認（任意）
```bash
   composer -V
   ```
- ⑦Laravel 用ディレクトリを作成（人によって違うディレクトリ）
```bash
   mkdir laravel
   cd Laravel
   ```
- ⑧Composer を使って Laravel をインストール
```bash
   php ~/composer.phar create-project laravel/laravel .
   ```
   ※ 最後の . は「このディレクトリにインストールする」という意味。
- ⑨インストールが成功すると…
     ⑦で作成したディレクトリ配下に下記の構成ができる。
       app/
       bootstrap/
       config/
       public/
       resources/
       routes/
       vendor/
       .env
       artisan



### クエリ最適化
eager loading で N+1 クエリ問題を防止

```php
// rooms()、messages() の relationship をロード
$rooms = Room::with('messages', 'user')->get();
```

## ライセンス

MIT License

## サポート

問題が発生した場合は、GitHub Issues で報告してください。またはお問い合わせフォーム `/api/contact` から通知をお送りください。

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
