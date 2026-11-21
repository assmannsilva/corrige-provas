# Corrige Provas

Sistema para professores criarem provas, gerarem PDFs, enviarem digitalizações e revisarem a correção. Questões objetivas usam OMR (análise local das marcações); respostas discursivas usam transcrição e correção assistidas por IA. O professor mantém a decisão final sobre as notas.

## Requisitos e versões

As versões abaixo vêm dos arquivos do projeto, não de suposições sobre a máquina:

| Componente | Versão |
| --- | --- |
| PHP | 8.5 (Dockerfile e composer.json) |
| Laravel | 13.17.0 (composer.lock) |
| Inertia Laravel / Vue | 3.1.0 / 3.5.0 |
| Vue | 3.5.39 |
| Vite / Tailwind CSS | 8.1.0 / 4.3.1 |
| Node.js | 22 no Dockerfile (mínimo 22.12 nessa série) |
| Python | 3.12 no Dockerfile |
| MySQL / Redis | imagens 9 / 8-alpine |
| Browsershot / Puppeteer | 5.4.0 / 25.11.0 |

Use Docker Engine com Compose v2 e Git. O serviço Python instala FastAPI, Uvicorn, OpenCV, NumPy e PyMuPDF conforme `docker/python/requirements.txt`; essas dependências ainda não têm versões fixadas. Os locks PHP e JavaScript devem ser preservados.

## Instalação local

Execute na raiz do projeto, em uma cópia sem dados reais:

```sh
cp .env.example .env
```

Edite `.env` para usar os serviços do Compose:

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

As senhas do Compose são exemplos exclusivos para desenvolvimento local. O Compose publica portas no host; execute numa rede de confiança, sem expor o ambiente à internet. Para outro ambiente, configure credenciais próprias e `APP_DEBUG=false`.

Instale as dependências e prepare o banco:

```sh
docker compose build
docker compose up -d --wait mysql redis
docker compose run --rm php composer install --no-interaction --prefer-dist
docker compose run --rm php php artisan key:generate
docker compose run --rm php php artisan migrate --no-interaction
docker compose run --rm -e PUPPETEER_SKIP_DOWNLOAD=true php npm ci
docker compose run --rm php npm run build
docker compose up -d
```

O Chromium é instalado na imagem PHP e usado para gerar os PDFs. Acesse **http://localhost:8080**. O primeiro build pode demorar; consulte `docker compose ps` se algum serviço não subir.

Não há cadastro público nem conta com senha predefinida. Crie o professor localmente com `docker compose exec php php artisan tinker`, escolhendo seus próprios valores:

```php
$user = new App\Models\User;
$user->name = 'Professor de avaliação';
$user->email = 'professor@example.test';
$user->password = Illuminate\Support\Facades\Hash::make('SUBSTITUA-POR-UMA-SENHA-LOCAL');
$user->save();
```

Saia do Tinker com `exit` e entre com essa conta. O endereço `example.test` é fictício e não recebe mensagens.

## Configuração da IA

A transcrição e a correção discursiva usam OpenRouter. Configure a sua chave somente no `.env` local:

```dotenv
OPENROUTER_API_KEY=
OPENROUTER_MODEL=google/gemini-3.7-flash
GRADING_LLM_MODEL=google/gemini-3.7-flash
GRADING_LLM_URL=https://openrouter.ai/api/v1/chat/completions
```

O padrão nas duas etapas é [Gemini 3.7 Flash no OpenRouter](https://openrouter.ai/google/gemini-3.7-flash). A conta usada na avaliação precisa ter acesso ao modelo. Não há fallback silencioso para outro modelo; mudar essa configuração pode alterar os resultados. Os testes automatizados não fazem chamadas autenticadas ao provedor.

Depois de alterar essas variáveis, recrie os serviços que as utilizam:

```sh
docker compose up -d --force-recreate php worker scheduler python
docker compose exec php php artisan config:clear
```

Sem chave, a transcrição e a correção discursiva não funcionam; erros ficam associados ao processamento para revisão e nova tentativa. OMR e correção de alternativas não usam IA. Não existe modo demo ou respostas simuladas no fluxo de uso.

## Como usar

1. Entre como professor, crie uma prova e adicione questões discursivas, de escolha única ou de múltipla escolha. Configure pesos, gabaritos e critérios.
2. Publique a prova, gere e baixe o PDF. A geração registra as regiões de resposta usadas na leitura.
3. Preencha uma cópia com conteúdo fictício para avaliação do sistema. Digitalize o PDF ou fotografe suas páginas.
4. Na prova publicada, selecione os arquivos e confira a ordem. Ao clicar em **Enviar**, o modal solicita o **nome do aluno**, obrigatório e digitado manualmente, e a **turma**, opcional. Confirme o envio.
5. Aguarde a fila de extração e correção. O nome digitado é armazenado apenas localmente.
6. Abra a submissão, confira os recortes, as respostas, os avisos de revisão e as notas. Ajuste identificação ou notas quando necessário.
7. Use **Reprocessar** para executar novamente a leitura e a correção. O nome informado pelo professor é preservado; respostas e notas podem ser recalculadas.

Uma submissão representa a prova de um aluno e pode conter um PDF ou várias imagens. Não misture provas de alunos diferentes em um único envio.

## Tratamento de dados

- Nome e turma ficam no banco local e não integram os payloads de extração ou correção.
- O Laravel envia caminhos internos, template e regiões de respostas ao serviço Python da rede Docker. Os arquivos originais são processados localmente nesse serviço.
- O Python envia ao OpenRouter somente o recorte exato da região de resposta discursiva, sem recortar ou reconhecer o cabeçalho. A transcrição utiliza três amostras e marca divergências para revisão.
- A correção discursiva envia enunciado, gabarito, critérios, peso e texto da resposta. Questões objetivas são processadas localmente.
- Isso não anonimiza conteúdo escrito dentro da própria resposta: nomes ou outros dados pessoais nessa área podem chegar ao provedor. Para apresentação à banca, use apenas conteúdo fictício e revise os materiais antes de enviar.
- Originais e recortes ficam em `storage/app/private`; banco e registros de processamento permanecem locais. Não existe exclusão automática por prazo. O responsável pela instalação deve definir retenção e eliminar o ambiente de avaliação quando não for mais necessário.
- Não compartilhe `.env`, chaves, bancos, volumes, logs, digitalizações ou capturas com identificação. Os testes usam arquivos sintéticos e clientes HTTP interceptados.

## Arquitetura e processamento

Laravel organiza autorização, submissões, filas e correção; Vue/Inertia fornece a interface. O serviço Python alinha páginas com OpenCV, lê marcações por densidade de tinta e transcreve regiões discursivas. Os resultados são associados às questões por identificador.

A persistência atualiza respostas sem duplicá-las em novas tentativas. Falhas são registradas no processamento. O intervalo de liberação da fila (`retry_after=360`) deve permanecer maior que o timeout do worker (300 segundos), evitando que uma tarefa ainda ativa seja entregue novamente por expiração da reserva. Provas extensas podem exigir revisão coordenada dos limites HTTP e da fila.

Se uma prova ficar pendente, confira `docker compose ps` e `docker compose logs --tail=100 worker python`. Se houver erro de geração do PDF, confira também os logs de `php` e `nginx`. Não compartilhe logs sem revisar os dados que aparecem neles.

## Testes e verificações

Os testes não precisam de chave real de IA. Na imagem PHP:

```sh
docker compose run --rm php php artisan test --compact tests/Feature/Submission
docker compose run --rm php php artisan test --compact
docker compose run --rm php composer types:check
docker compose run --rm php composer lint:check
docker compose run --rm php npm run lint:check
docker compose run --rm php npm run types:check
docker compose run --rm php npm run build
```

O build e o Wayfinder precisam de PHP e das dependências Composer instaladas. O PHPUnit usa SQLite em memória; o PHP de testes precisa da extensão `pdo_sqlite`.

Para Python, use um ambiente virtual, sem credenciais:

```sh
python3.12 -m venv .venv
. .venv/bin/activate
pip install -r docker/python/requirements.txt pytest httpx
python -m pytest python/tests -q
```

As regressões cobrem nome obrigatório, autorização, envio sem turma, ausência de identificação nos payloads, recorte exato da resposta, preservação do nome no reprocessamento, OMR e seleção configurável do modelo. A validação visual completa e os testes integrados exigem todos os serviços em execução.
