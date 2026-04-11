FROM php:8.4-fpm

# 1. Instalar dependências (Sistema + PHP + Imagick/HEIF + Python/Rembg)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    python3 \
    python3-pip \
    python-is-python3 \
    libgl1 \
    libglib2.0-0 \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    tzdata \
    imagemagick \
    libheif1 \
    libheif-dev \
    libmagickwand-dev \
    pkg-config \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 1.5 Instalar bibliotecas de IA do Python globalmente
RUN pip3 install --break-system-packages "rembg[cpu]" pillow

# 2. Configurar Timezone do Sistema e do PHP
ENV TZ=America/Sao_Paulo
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
RUN echo "date.timezone=America/Sao_Paulo" > /usr/local/etc/php/conf.d/99-timezone.ini

# 3. Corrigir erro do Git
RUN git config --global --add safe.directory /var/www

# 4. Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Configurar diretório de trabalho
WORKDIR /var/www

EXPOSE 9000

CMD ["php-fpm"]