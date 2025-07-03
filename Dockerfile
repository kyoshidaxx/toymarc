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
    libzip-dev

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

# FrankenPHPの設定
ENV FRANKENPHP_CONFIG=/etc/caddy/Caddyfile

# Caddyfileをコピー
COPY Caddyfile /etc/caddy/Caddyfile

# ポート80を公開
EXPOSE 80

# FrankenPHPを起動
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"] 