-- 訪問先サイトの情報を保存するテーブル
CREATE TABLE IF NOT EXISTS feeds (
    -- 訪問先サイトの識別番号
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    -- 訪問先サイト名
    title TEXT NOT NULL,
    -- フィード配信先URL
    url TEXT NOT NULL UNIQUE,
    -- 最終更新日時
    updated_at INTEGER NOT NULL,
    -- 登録日時
    created_at INTEGER NOT NULL,
    CHECK (updated_at >= created_at)
);

-- 天気予報の地域とその登録先のペアを保存するテーブル
CREATE TABLE if not exists locations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    -- 地域を示す一意な番号
    place_id TEXT NOT NULL,
    -- 最終更新日時
    updated_at INTEGER NOT NULL,
    -- 登録日時
    created_at INTEGER NOT NULL
);

-- 記事の情報を保存するテーブル
CREATE TABLE IF NOT EXISTS articles (
    -- 記事の識別番号
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    -- 記事のタイトル
    title TEXT NOT NULL,
    -- 記事URL
    url TEXT NOT NULL UNIQUE,
    -- 記事公開日時
    updated_at INTEGER NOT NULL,
    -- 登録日時
    created_at INTEGER NOT NULL,
    -- 訪問先サイト識別番号
    feed_id INTEGER NOT NULL,
    -- 訪問先サイト
    FOREIGN KEY (feed_id) REFERENCES feeds (id),
    CHECK (created_at >= updated_at)
);

-- Webhook の投稿先の情報を保存するテーブル
CREATE TABLE IF NOT EXISTS webhooks (
    -- 投稿先の識別番号
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    -- わかりやすいような任意のタイトル
    title TEXT,
    -- 投稿先エンドポイント
    url TEXT NOT NULL,
    -- 更新日時
    updated_at INTEGER NOT NULL,
    -- 登録日時
    created_at INTEGER NOT NULL,
    -- 用途（Webhook / フィード） 
    source_id INTEGER NOT NULL,
    FOREIGN KEY (source_id) REFERENCES sources (id),
    CHECK (updated_at >= created_at)
);

-- 投稿履歴を保存するテーブル
CREATE TABLE IF NOT EXISTS post_history (
    -- 投稿履歴の識別番号
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    -- 投稿日時
    post_date INTEGER NOT NULL,
    -- 投稿先識別番号
    webhook_id INTEGER NOT NULL,
    -- 記事識別番号
    article_id INTEGER,
    -- 天気予報の地域とその登録先のペアの識別番号
    location_id INTEGER,
    -- 用途（Webhook / フィード） 
    source_id INTEGER NOT NULL,
    -- Webhook の投稿先の識別番号
    FOREIGN KEY (webhook_id) REFERENCES webhooks (id),
    -- 投稿記事
    FOREIGN KEY (article_id) REFERENCES articles (id),
    -- 天気予報の地域とその登録先のペアの識別番号
    FOREIGN KEY (location_id) REFERENCES locations (id),
    FOREIGN KEY (source_id) REFERENCES sources (id)
);

-- 何の機能が実装されているかを保存するテーブル
CREATE TABLE if not exists sources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL
);

INSERT INTO
    sources (name)
VALUES
    ('Forecast');

INSERT INTO
    sources (name)
VALUES
    ('feed');
