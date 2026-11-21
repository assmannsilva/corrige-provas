SHELL := /bin/sh
.DEFAULT_GOAL := help

.PHONY: help all setup env build infra install key migrate migrate-fresh teacher up down restart assets test lint logs ps

help:
	@printf '%s\n' \
		'make all           - instala, configura e inicia o projeto' \
		'make setup         - alias de make all' \
		'make env           - cria o .env para Docker/MySQL se estiver ausente' \
		'make build         - constrói as imagens Docker' \
		'make install       - instala dependências Composer e NPM' \
		'make up            - inicia os contêineres' \
		'make down          - para os contêineres sem apagar dados' \
		'make restart       - reinicia os contêineres' \
		'make migrate       - executa as migrations' \
		'make migrate-fresh - recria o banco após confirmação (apaga dados)' \
		'make teacher       - cria ou redefine o professor de demonstração' \
		'make assets        - compila o frontend' \
		'make test          - executa os testes Laravel' \
		'make lint          - verifica formatação e lint' \
		'make logs          - acompanha os logs' \
		'make ps            - lista os contêineres'

all: env build infra install key migrate assets up teacher

setup: all

env:
	@if [ -f .env ]; then \
		echo '.env existente preservado.'; \
	else \
		sed \
			-e 's|^APP_URL=.*|APP_URL=http://localhost:8080|' \
			-e 's|^DB_CONNECTION=.*|DB_CONNECTION=mysql|' \
			-e 's|^# DB_HOST=.*|DB_HOST=mysql|' \
			-e 's|^# DB_PORT=.*|DB_PORT=3306|' \
			-e 's|^# DB_DATABASE=.*|DB_DATABASE=tcc|' \
			-e 's|^# DB_USERNAME=.*|DB_USERNAME=tcc|' \
			-e 's|^# DB_PASSWORD=.*|DB_PASSWORD=secret|' \
			-e 's|^REDIS_HOST=.*|REDIS_HOST=redis|' \
			.env.example > .env; \
		echo '.env criado para o ambiente Docker/MySQL.'; \
	fi

build:
	docker compose build

infra:
	docker compose up -d --wait mysql redis

install:
	docker compose run --rm php composer install --no-interaction --prefer-dist
	docker compose run --rm -e PUPPETEER_SKIP_DOWNLOAD=true php npm ci

key:
	@if grep -q '^APP_KEY=$' .env; then \
		docker compose run --rm php php artisan key:generate; \
	else \
		echo 'APP_KEY existente preservada.'; \
	fi

migrate:
	docker compose run --rm php php artisan migrate --no-interaction

migrate-fresh:
	@printf 'ATENÇÃO: todos os dados do banco serão apagados. Digite APAGAR para continuar: '; \
	read answer; \
	if [ "$$answer" != 'APAGAR' ]; then echo 'Operação cancelada.'; exit 1; fi
	docker compose run --rm php php artisan migrate:fresh --force

teacher:
	docker compose run --rm php php artisan demo:teacher

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart

assets:
	docker compose run --rm php npm run build

test:
	docker compose run --rm php php artisan test --compact

lint:
	docker compose run --rm php composer lint:check
	docker compose run --rm php npm run format:check
	docker compose run --rm php npm run lint:check

logs:
	docker compose logs -f --tail=100 php worker python

ps:
	docker compose ps
