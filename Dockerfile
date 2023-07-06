FROM php:7.4-fpm

# 安装依赖
RUN apt-get update && apt-get install -y \
    git \
    libzip-dev \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        gd \
        sockets \
        zip \
    && pecl install swoole-4.6.7 \
    && docker-php-ext-enable swoole \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*
# 安装 Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 设置工作目录
WORKDIR /var/www/app

# 安装依赖
COPY ./app/composer.json ./app/composer.lock ./
RUN composer install --no-scripts --no-autoloader

# 添加应用代码
COPY . .

# 生成 Autoload 文件
RUN composer dump-autoload --optimize

# Expose port 9501
EXPOSE 9501

# 启动 Hyperf
CMD ["php", "/var/www/app/bin/hyperf.php", "start"]
