SHELL := /bin/sh
.DEFAULT_GOAL := help

.PHONY: help setup up down teacher test lint build logs

help:
	@printf '%s\n' 'make setup    - instala o projeto e cria o professor de demonstração' 'make up       - inicia os serviços' 'make down     - encerra os serviços' 'make teacher  - cria ou redefine a conta do professor' 'make test     - executa os testes Laravel' 'make lint     - verifica a formatação PHP e frontend' 'make build    - gera os arquivos do frontend' 'make logs     - acompanha os logs'

setup:
	@test -f .env || cp .env.example .env
	docker compose build
	docker compose up -d --wait mysql redis
	docker compose run --rm php composer install --no-interaction --prefer-dist
	docker compose run --rm php php artisan key:generate
	docker compose run --rm php php artisan migrate --no-interaction
	docker compose run --rm -e PUPPETEER_SKIP_DOWNLOAD=true php npm ci
	docker compose run --rm php npm run build
	docker compose up -d
	$(MAKE) teacher

up:
	docker compose up -d

down:
	docker compose down

teacher:
	docker compose exec -T php php artisan demo:teacher

test:
	docker compose exec -T php php artisan test --compact

lint:
	docker compose exec -T php composer lint:check
	docker compose exec -T php npm run format:check
	docker compose exec -T php npm run lint:check

build:
	docker compose exec -T php npm run build

logs:
	docker compose logs -f --tail=100 php worker python
