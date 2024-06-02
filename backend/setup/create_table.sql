BEGIN TRANSACTION;

CREATE TABLE IF NOT EXISTS "articles" (
	"id" INTEGER NOT NULL UNIQUE,
	"title" TEXT NOT NULL,
	"url" TEXT NOT NULL,
	"feed_id" INTEGER NOT NULL,
	"updated_at" INTEGER NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT),
	UNIQUE("url"),
	FOREIGN KEY("feed_id") REFERENCES "feeds"("id")
);

CREATE TABLE IF NOT EXISTS "cache_forecast" (
	"location_id" INTEGER NOT NULL UNIQUE,
	"content" TEXT NOT NULL,
	FOREIGN KEY("location_id") REFERENCES "locations"("id")
);

CREATE TABLE IF NOT EXISTS "feeds" (
	"id" INTEGER NOT NULL UNIQUE,
	"title" TEXT NOT NULL,
	"url" TEXT NOT NULL,
	"updated_at" INTEGER NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT),
	UNIQUE("url")
);

CREATE TABLE IF NOT EXISTS "locations" (
	"id" INTEGER NOT NULL UNIQUE,
	"place_id" TEXT NOT NULL UNIQUE,
	"updated_at" INTEGER NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT)
);

CREATE TABLE IF NOT EXISTS "post_history_feed" (
	"id" INTEGER NOT NULL UNIQUE,
	"posted_at" INTEGER NOT NULL,
	"webhook_id" INTEGER NOT NULL,
	"article_id" INTEGER NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT),
	UNIQUE("webhook_id", "article_id"),
	FOREIGN KEY("article_id") REFERENCES "articles"("id"),
	FOREIGN KEY("webhook_id") REFERENCES "webhooks"("id")
);

CREATE TABLE IF NOT EXISTS "post_history_forecast" (
	"id" INTEGER NOT NULL UNIQUE,
	"posted_at" INTEGER NOT NULL,
	"webhook_id" INTEGER NOT NULL,
	"location_id" INTEGER NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT),
	UNIQUE("webhook_id", "location_id"),
	FOREIGN KEY("location_id") REFERENCES "locations"("id"),
	FOREIGN KEY("webhook_id") REFERENCES "webhooks"("id")
);

CREATE TABLE IF NOT EXISTS "sources" (
	"id" INTEGER NOT NULL UNIQUE,
	"name" TEXT NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT),
	UNIQUE("id", "name")
);

CREATE TABLE IF NOT EXISTS "webhook_map_feed" (
	"id" INTEGER NOT NULL UNIQUE,
	"webhook_id" INTEGER NOT NULL,
	"feed_id" INTEGER NOT NULL,
	"enabled" INTEGER NOT NULL DEFAULT 1,
	PRIMARY KEY("id" AUTOINCREMENT),
	UNIQUE("webhook_id", "feed_id"),
	FOREIGN KEY("feed_id") REFERENCES "feeds"("id"),
	FOREIGN KEY("webhook_id") REFERENCES "webhooks"("id")
);

CREATE TABLE IF NOT EXISTS "webhook_map_forecast" (
	"id" INTEGER NOT NULL UNIQUE,
	"webhook_id" INTEGER NOT NULL,
	"location_id" INTEGER NOT NULL,
	"enabled" INTEGER NOT NULL DEFAULT 1,
	PRIMARY KEY("id" AUTOINCREMENT),
	UNIQUE("webhook_id", "location_id"),
	FOREIGN KEY("location_id") REFERENCES "locations"("id"),
	FOREIGN KEY("webhook_id") REFERENCES "webhooks"("id")
);

CREATE TABLE IF NOT EXISTS "webhooks" (
	"id" INTEGER NOT NULL UNIQUE,
	"title" TEXT NOT NULL DEFAULT '',
	"url" TEXT NOT NULL UNIQUE,
	PRIMARY KEY("id" AUTOINCREMENT)
);

COMMIT;