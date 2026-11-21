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

Na raiz do repositório, configure o `.env` para usar o MySQL do Docker:

```bash
cp .env.example .env
```

Ajuste estas variáveis no arquivo `.env`:

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

Depois execute:

```bash
make setup
```

O comando constrói as imagens Docker, inicia MySQL e Redis, instala as dependências, configura a chave da aplicação, executa as migrations, compila o frontend e inicia os serviços. Ao final, cria uma conta de professor de demonstração e mostra as credenciais no terminal.

Acesse **http://localhost:8080**. O Chromium já faz parte da imagem PHP e é utilizado na geração das provas em PDF.

As credenciais de banco presentes no Compose são destinadas ao desenvolvimento local. Não exponha essas portas a redes externas sem configurar autenticação e isolamento apropriados.

### Conta de professor para demonstração

O `make setup` cria automaticamente um professor com o e-mail `professor@example.test` e uma **senha aleatória**, exibida no terminal. Anote a senha para entrar na aplicação.

Para criar a conta posteriormente ou redefinir sua senha, execute:

```bash
make teacher
```

O comando atualiza a mesma conta, sem criar duplicatas. A cada execução, uma nova senha é gerada e exibida. A criação da conta de demonstração é permitida apenas nos ambientes `local` e `testing`.

Outros comandos úteis:

```bash
make up       # Inicia os serviços
make down     # Encerra os serviços
make test     # Executa os testes Laravel
make lint     # Verifica formatação e lint
make build    # Compila o frontend
make logs     # Acompanha os logs
```

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
