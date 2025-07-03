FROM dunglas/frankenphp:1-alpine

# 必要なパッケージをインストール
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite \
    sqlite-dev \
    oniguruma-dev \
    libzip-dev \
    icu-dev \
    nodejs \
    npm

# PHP拡張機能をインストール
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl

# Composerをインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 作業ディレクトリを設定
WORKDIR /var/www/html

# アプリケーションコードをコピー
COPY src/ .

# 依存関係をインストール（開発用も含む）
RUN composer install --optimize-autoloader
RUN npm install

# 環境設定ファイルをコピー
COPY src/.env.example .env

# アプリケーションキーを生成
RUN php artisan key:generate

# 権限を設定
RUN chmod -R 777 storage bootstrap/cache

# フロントエンドをビルド
RUN npm run build

# FrankenPHPの設定
ENV FRANKENPHP_CONFIG=/etc/caddy/Caddyfile

# Caddyfileをコピー
COPY infra/Caddyfile /etc/caddy/Caddyfile

# ポート80を公開
EXPOSE 80

# 起動スクリプトを作成
COPY infra/start.sh /start.sh
RUN chmod +x /start.sh

# 起動スクリプトを実行
CMD ["/start.sh"] 