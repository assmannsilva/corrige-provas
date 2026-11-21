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

Tenha Docker Engine, Docker Compose v2 e GNU Make instalados. Na raiz do repositório, execute:

```bash
make all
```

Na primeira execução, o Makefile cria automaticamente o `.env` com a configuração de MySQL e Redis do Docker, constrói as imagens, instala as dependências, gera a chave da aplicação, executa as migrations, compila o frontend, inicia os serviços e prepara a conta de professor.

**Se o `.env` já existir, ele será preservado integralmente.** Nesse caso, confira se as configurações de banco e Redis apontam para os serviços do Docker (`DB_HOST=mysql`, `REDIS_HOST=redis`), pois o Makefile não modifica configurações existentes.

Acesse **http://localhost:8080**. O Chromium já faz parte da imagem PHP e é utilizado na geração das provas em PDF.

As credenciais de banco presentes no Compose são destinadas ao desenvolvimento local. Não exponha essas portas a redes externas sem configurar autenticação e isolamento apropriados.

### Conta de professor para demonstração

O `make all` cria uma conta de professor com estas credenciais:

| Campo | Valor |
| --- | --- |
| Nome | Professor de Demonstração |
| E-mail | `professor@example.test` |
| Senha | `password` |

A senha é armazenada com hash pelo Laravel. O comando `make teacher` cria ou atualiza essa mesma conta, sem duplicá-la, e redefine a senha para `password`. A conta só pode ser criada em ambientes `local` ou `testing` e **não deve ser utilizada em produção**.

### Comandos Make

| Comando | Ação |
| --- | --- |
| `make all` | Prepara o ambiente completo e cria o professor |
| `make setup` | Alias de `make all` |
| `make env` | Cria o `.env` para Docker/MySQL somente se não existir |
| `make build` | Constrói as imagens Docker |
| `make install` | Instala dependências Composer e NPM |
| `make up` | Inicia os contêineres |
| `make down` | Encerra os contêineres, preservando os volumes |
| `make restart` | Reinicia os contêineres |
| `make migrate` | Executa as migrations |
| `make migrate-fresh` | Recria as tabelas após confirmação; apaga os dados |
| `make teacher` | Cria ou redefine a conta do professor |
| `make assets` | Compila o frontend |
| `make test` | Executa os testes Laravel |
| `make lint` | Verifica a formatação PHP e frontend |
| `make logs` | Acompanha os logs |
| `make ps` | Lista os contêineres |

Execute `make help` para consultar os comandos no terminal.

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
