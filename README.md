# Corrige Provas

Aplicação para criação e correção de avaliações impressas. O professor prepara as questões, gera o PDF da prova, envia as páginas preenchidas e confere os resultados. A leitura das marcações objetivas é feita por processamento de imagem; as respostas discursivas passam por transcrição e correção assistidas por IA. As notas podem ser revisadas pelo professor.

## Tecnologias

- **Backend:** PHP 8.5, Laravel 13, filas e MySQL.
- **Frontend:** Vue 3, TypeScript, Inertia.js e Tailwind CSS 4.
- **Leitura das provas:** Python 3.12, FastAPI, OpenCV e PyMuPDF.
- **IA:** OpenRouter, com `google/gemini-3.7-flash` configurado por padrão.
- **Infraestrutura:** Docker Compose, Redis, Nginx e Chromium para os PDFs.

As versões exatas das dependências estão em `composer.lock`, `package-lock.json` e nos Dockerfiles. Para executar o projeto, tenha Docker Engine e Docker Compose v2 instalados.

## Instalação

Na raiz do repositório:

```bash
cp .env.example .env
```

Ajuste estas variáveis no `.env` para usar os serviços do Docker:

```dotenv
APP_URL=http://localhost:8080
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=tcc
DB_USERNAME=tcc
DB_PASSWORD=secret
REDIS_HOST=redis
COMPUTER_VISION_SERVICE_URL=http://python:8000
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=360
REDIS_QUEUE_RETRY_AFTER=360
```

Em seguida, prepare a aplicação:

```bash
docker compose build
docker compose up -d --wait mysql redis
docker compose run --rm php composer install --no-interaction --prefer-dist
docker compose run --rm php php artisan key:generate
docker compose run --rm php php artisan migrate --no-interaction
docker compose run --rm -e PUPPETEER_SKIP_DOWNLOAD=true php npm ci
docker compose run --rm php npm run build
docker compose up -d
```

Acesse **http://localhost:8080**. O Chromium já faz parte da imagem PHP e é utilizado na geração das provas em PDF.

As credenciais de banco presentes no Compose são destinadas ao desenvolvimento local. Não exponha essas portas a redes externas sem configurar autenticação e isolamento apropriados.

### Conta de professor

O projeto não cria uma conta de acesso automaticamente. Para cadastrar um usuário no ambiente local, execute:

```bash
docker compose exec php php artisan tinker
```

No Tinker, informe nome, e-mail e uma senha de sua escolha:

```php
$user = new App\Models\User;
$user->name = 'Professor';
$user->email = 'professor@example.test';
$user->password = Illuminate\Support\Facades\Hash::make('SENHA-LOCAL-AQUI');
$user->save();
```

Depois, saia com `exit` e acesse a aplicação. O endereço `example.test` é apenas ilustrativo.

## Configuração da IA

As etapas de transcrição e correção discursiva utilizam o OpenRouter. Informe a chave e, se necessário, altere os modelos no `.env`:

```dotenv
OPENROUTER_API_KEY=
OPENROUTER_MODEL=google/gemini-3.7-flash
GRADING_LLM_MODEL=google/gemini-3.7-flash
GRADING_LLM_URL=https://openrouter.ai/api/v1/chat/completions
```

O modelo precisa estar disponível na conta utilizada. Após editar a configuração, recrie os serviços e limpe o cache:

```bash
docker compose up -d --force-recreate php worker scheduler python
docker compose exec php php artisan config:clear
```

A leitura de alternativas utiliza OMR local; a transcrição das questões discursivas requer uma chave válida do OpenRouter.

## Fluxo de uso

1. Entre com a conta de professor, crie uma prova e configure questões, alternativas, pesos e critérios de correção.
2. Publique a prova, gere o PDF e imprima as páginas.
3. Após o preenchimento, fotografe ou digitalize a prova.
4. Selecione os arquivos na aplicação, confira a ordem das páginas e informe o nome do aluno no diálogo de envio. A turma é opcional.
5. Aguarde o processamento e consulte as respostas extraídas, os recortes e as notas atribuídas.
6. Confira os resultados e, quando necessário, ajuste as notas ou a identificação. A ação de reprocessamento refaz a extração e a correção sem alterar o nome informado.

Cada envio corresponde a uma prova de um aluno. Um envio pode conter um PDF ou várias imagens de suas páginas.

## Processamento dos dados

O Laravel coordena os envios, a autorização e as filas. O serviço Python alinha as páginas e identifica as regiões das respostas. As alternativas são analisadas localmente; os recortes das respostas discursivas são encaminhados ao OpenRouter para transcrição. A correção considera o enunciado, a resposta extraída, o gabarito e os critérios definidos pelo professor.

A identificação digitada no envio permanece nos registros locais da submissão. Entretanto, se o próprio aluno escrever dados pessoais dentro do espaço destinado à resposta, esse conteúdo pode integrar o recorte enviado ao provedor. Para demonstrações e testes, utilize avaliações fictícias.

Os documentos e recortes são armazenados em `storage/app/private`. A aplicação não possui política automática de exclusão por prazo, portanto a retenção precisa ser gerenciada por quem opera a instalação.

## Testes e qualidade

Os testes automatizados utilizam dados sintéticos e não precisam de uma chave válida do OpenRouter.

Com os serviços Docker disponíveis:

```bash
docker compose run --rm php php artisan test --compact
docker compose run --rm php composer types:check
docker compose run --rm php composer lint:check
docker compose run --rm php npm run lint:check
docker compose run --rm php npm run format:check
docker compose run --rm php npm run types:check
docker compose run --rm php npm run build
```

Para executar os testes do serviço Python em um ambiente virtual com Python 3.12:

```bash
python3.12 -m venv .venv
. .venv/bin/activate
pip install -r docker/python/requirements.txt pytest httpx
python -m pytest python/tests -q
```

A suíte PHP usa SQLite em memória. Verifique se a extensão `pdo_sqlite` está disponível no ambiente utilizado para os testes.

## Diagnóstico

Confira o estado dos contêineres com `docker compose ps`. Para acompanhar a extração e a correção, consulte `docker compose logs --tail=100 worker python`. Para problemas na geração do PDF, verifique também os logs dos serviços `php` e `nginx`.
