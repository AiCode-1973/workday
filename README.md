# Workday

Sistema de gerenciamento de projetos inspirado no Monday.com, ClickUp e Asana. Desenvolvido com PHP puro (arquitetura MVC), MySQL e JavaScript vanilla — sem frameworks externos, sem etapa de build.

---

## Sumário

- [Visão geral](#visão-geral)
- [Tecnologias](#tecnologias)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Estrutura de pastas](#estrutura-de-pastas)
- [Banco de dados](#banco-de-dados)
- [Rotas da aplicação](#rotas-da-aplicação)
- [API pública](#api-pública)
- [WebSocket](#websocket)
- [E-mail](#e-mail)
- [Funcionalidades](#funcionalidades)
- [Usuários de demonstração](#usuários-de-demonstração)
- [Segurança](#segurança)

---

## Visão geral

O **Workday** é uma plataforma web de gerenciamento de tarefas e projetos com suporte a múltiplos quadros, visualizações Kanban / Lista / Tabela / Calendário, automações IFTTT, comentários, notificações em tempo real via WebSocket e uma API REST pública.

---

## Tecnologias

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.1+ puro — arquitetura MVC sem framework |
| Banco de dados | MySQL 5.7+ / MariaDB 10.4+ |
| Frontend | JavaScript ES6 vanilla (sem React/Vue/build step) |
| Estilo | CSS utilitário customizado (sem Tailwind/PostCSS) |
| Servidor web | Apache 2.4 (XAMPP) com `mod_rewrite` |
| WebSocket | Servidor PHP nativo com `ext-sockets` |
| E-mail | SMTP nativo PHP (sem Composer/PHPMailer) |

---

## Requisitos

- XAMPP (ou Apache + PHP + MySQL separados)
- PHP 8.1 ou superior com as extensões: `pdo_mysql`, `sockets`, `mbstring`, `fileinfo`
- MySQL 5.7+ ou MariaDB 10.4+
- `mod_rewrite` habilitado no Apache
- `AllowOverride All` configurado para a pasta `htdocs`

---

## Instalação

### 1. Clonar / copiar os arquivos

Coloque a pasta do projeto em:
```
C:\xampp1\htdocs\workday\
```

### 2. Criar o banco de dados e tabelas

**Opção A — Script automático (Windows):**
```
Clique duas vezes em setup.bat
```

**Opção B — Manual via linha de comando:**
```bash
mysql -u root workday < database/migrations/001_schema.sql
mysql -u root workday < database/migrations/002_seeds.sql
```

**Opção C — phpMyAdmin:**
1. Crie o banco `workday` com charset `utf8mb4`
2. Importe `database/migrations/001_schema.sql`
3. Importe `database/migrations/002_seeds.sql`

### 3. Criar pasta de uploads

```bash
mkdir public/uploads
```
> O `setup.bat` já faz isso automaticamente.

### 4. Acessar o sistema

```
http://localhost/workday
```

---

## Configuração

Todos os parâmetros ficam em `config/config.php`:

```php
// Banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'workday');
define('DB_USER', 'root');
define('DB_PASS', '');

// URL base da aplicação
define('APP_URL', 'http://localhost/workday');

// Ambiente: 'development' exibe erros detalhados
define('APP_ENV', 'development');

// E-mail
define('MAIL_DRIVER', 'log');   // 'log' (grava em arquivo) | 'smtp' | 'mail'
define('MAIL_HOST',   'smtp.mailtrap.io');
define('MAIL_PORT',   587);
define('MAIL_USERNAME', '');
define('MAIL_PASSWORD', '');

// WebSocket
define('WS_PORT', 8080);

// Uploads
define('UPLOAD_MAX_SIZE', 10485760); // 10 MB
```

### Alterar para produção

```php
define('APP_ENV',   'production');
define('APP_DEBUG', false);
define('APP_URL',   'https://seudominio.com');
define('SECRET_KEY', 'chave-unica-e-segura-aqui');
define('MAIL_DRIVER', 'smtp');
```

---

## Estrutura de pastas

```
workday/
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php          # Login, registro, recuperação de senha
│   │   ├── DashboardController.php     # Página inicial com estatísticas
│   │   ├── BoardController.php         # CRUD de quadros e grupos
│   │   ├── ItemController.php          # CRUD de tarefas, comentários, uploads
│   │   ├── NotificationController.php  # Notificações do usuário
│   │   ├── AutomationController.php    # CRUD de automações
│   │   ├── ReportController.php        # Relatórios e export CSV
│   │   ├── ProfileController.php       # Perfil, senha e tokens de API
│   │   ├── ApiController.php           # API REST pública (Bearer token)
│   │   └── BaseController.php          # Classe base com helpers
│   ├── Models/
│   │   ├── UserModel.php
│   │   ├── BoardModel.php
│   │   ├── ItemModel.php
│   │   └── BaseModel.php               # CRUD genérico com PDO
│   ├── Services/
│   │   ├── AutomationService.php       # Motor IFTTT de automações
│   │   └── MailService.php             # Envio de e-mails
│   ├── Router.php                      # Roteador regex com suporte a {params}
│   └── bootstrap.php                   # Sessão, CSRF, headers de segurança
│
├── config/
│   ├── config.php                      # Todas as constantes da aplicação
│   └── database.php                    # Singleton PDO
│
├── database/
│   └── migrations/
│       ├── 001_schema.sql              # Criação de todas as tabelas
│       └── 002_seeds.sql               # Dados de demonstração
│
├── public/
│   ├── index.php                       # Front controller (ponto de entrada)
│   ├── .htaccess                       # Rewrite para index.php
│   ├── css/
│   │   └── app.css                     # Estilos completos da aplicação
│   ├── js/
│   │   └── app.js                      # SPA JavaScript (~1000 linhas)
│   └── uploads/                        # Arquivos enviados pelos usuários
│
├── routes/
│   └── web.php                         # Registro de todas as rotas
│
├── views/
│   ├── layouts/
│   │   └── app.php                     # Layout principal com sidebar
│   ├── auth/
│   │   ├── login.php
│   │   ├── register.php
│   │   └── forgot.php
│   ├── dashboard/
│   │   └── index.php
│   ├── boards/
│   │   ├── index.php
│   │   └── show.php
│   ├── reports/
│   │   └── index.php
│   ├── profile/
│   │   └── index.php
│   └── errors/
│       ├── 404.php
│       └── 403.php
│
├── storage/
│   ├── logs/                           # Logs de e-mail (modo 'log')
│   └── cache/
│
├── .htaccess                           # Redireciona tudo para public/
├── websocket_server.php                # Servidor WebSocket PHP nativo
└── setup.bat                          # Script de instalação (Windows)
```

---

## Banco de dados

O schema contém **22 tabelas**:

| Tabela | Descrição |
|---|---|
| `users` | Usuários do sistema |
| `workspaces` | Espaços de trabalho (empresas/times) |
| `workspace_members` | Membros por workspace com papel (admin/member/viewer) |
| `portfolios` | Pastas para agrupar quadros |
| `boards` | Quadros de projetos |
| `board_members` | Membros por quadro |
| `board_groups` | Grupos/colunas dentro de um quadro |
| `board_fields` | Campos customizados do quadro |
| `items` | Tarefas/cards |
| `item_field_values` | Valores dos campos customizados por tarefa |
| `item_assignees` | Responsáveis por tarefa |
| `labels` | Etiquetas coloridas |
| `item_labels` | Etiquetas atribuídas a tarefas |
| `item_dependencies` | Dependências entre tarefas |
| `comments` | Comentários com suporte a threads |
| `attachments` | Arquivos anexados às tarefas |
| `activity_logs` | Histórico de ações |
| `notifications` | Notificações por usuário |
| `automations` | Regras de automação IFTTT |
| `webhooks` | Webhooks de integração |
| `api_tokens` | Tokens de acesso à API pública |
| `user_sessions` | Sessões ativas dos usuários |

---

## Rotas da aplicação

### Autenticação
| Método | Rota | Descrição |
|---|---|---|
| GET | `/login` | Formulário de login |
| POST | `/login` | Processar login |
| GET | `/register` | Formulário de cadastro |
| POST | `/register` | Criar conta |
| GET | `/logout` | Encerrar sessão |
| GET | `/forgot-password` | Recuperação de senha |
| POST | `/forgot-password` | Enviar e-mail de reset |

### Dashboard
| Método | Rota | Descrição |
|---|---|---|
| GET | `/` | Dashboard principal |
| GET | `/dashboard` | Dashboard principal |

### Quadros
| Método | Rota | Descrição |
|---|---|---|
| GET | `/boards` | Listar quadros |
| POST | `/boards` | Criar quadro |
| GET | `/boards/{id}` | Visualizar quadro |
| PUT | `/boards/{id}` | Atualizar quadro |
| POST | `/boards/{id}/archive` | Arquivar quadro |
| POST | `/boards/{id}/groups` | Criar grupo/coluna |
| PUT | `/boards/{id}/groups/{gid}` | Atualizar grupo |
| DELETE | `/boards/{id}/groups/{gid}` | Apagar grupo |

### Tarefas
| Método | Rota | Descrição |
|---|---|---|
| GET | `/boards/{id}/items` | Listar tarefas do quadro |
| POST | `/boards/{id}/items` | Criar tarefa |
| GET | `/items/{id}` | Detalhes da tarefa |
| PUT | `/items/{id}` | Atualizar tarefa |
| POST | `/items/{id}/move` | Mover entre grupos |
| POST | `/items/{id}/archive` | Arquivar tarefa |
| DELETE | `/items/{id}` | Apagar tarefa |
| GET | `/items/{id}/comments` | Listar comentários |
| POST | `/items/{id}/comments` | Adicionar comentário |
| POST | `/items/{id}/upload` | Anexar arquivo |

### Outras rotas
| Método | Rota | Descrição |
|---|---|---|
| GET | `/notifications` | Notificações do usuário |
| POST | `/notifications/{id}/read` | Marcar como lida |
| POST | `/notifications/read-all` | Marcar todas |
| GET | `/reports` | Página de relatórios |
| GET | `/reports/export-csv?board_id=X` | Exportar CSV do quadro |
| GET | `/profile` | Perfil do usuário |
| POST | `/profile` | Salvar perfil / avatar |
| POST | `/profile/password` | Alterar senha |
| POST | `/profile/tokens` | Criar token de API |
| DELETE | `/profile/tokens?id=X` | Revogar token |

---

## API pública

A API usa autenticação via **Bearer Token** no header `Authorization`.

### Criar um token

Acesse **Perfil → Tokens de API → Novo token** e copie o valor gerado.

### Endpoints disponíveis

```
GET /api/boards
Authorization: Bearer SEU_TOKEN
```
```
GET /api/boards/{id}/items
Authorization: Bearer SEU_TOKEN
```
```
GET /api/items/{id}
Authorization: Bearer SEU_TOKEN
```

### Exemplo com cURL

```bash
curl http://localhost/workday/api/boards \
  -H "Authorization: Bearer seu_token_aqui"
```

---

## WebSocket

O servidor WebSocket permite colaboração em tempo real (atualização automática ao mover cards, adicionar comentários etc.).

### Iniciar o servidor

```bash
php websocket_server.php
```

O servidor escuta na porta **8080** por padrão (configurável via `WS_PORT` em `config.php`).

O cliente JavaScript se conecta automaticamente ao abrir um quadro. Quando a conexão falha (servidor offline), a aplicação continua funcionando normalmente sem tempo real.

---

## E-mail

Por padrão, o `MAIL_DRIVER` está configurado como `log` — os e-mails **não são enviados**, apenas gravados em `storage/logs/mail_YYYY-MM-DD.log`. Isso é ideal para desenvolvimento.

### Configurar SMTP real

Edite `config/config.php`:

```php
define('MAIL_DRIVER',     'smtp');
define('MAIL_HOST',       'smtp.gmail.com');   // ou outro servidor
define('MAIL_PORT',       587);
define('MAIL_ENCRYPTION', 'tls');
define('MAIL_USERNAME',   'seu@email.com');
define('MAIL_PASSWORD',   'sua_senha_app');
define('MAIL_FROM',       'noreply@seudominio.com');
define('MAIL_FROM_NAME',  'Workday');
```

### E-mails enviados automaticamente

| Evento | Descrição |
|---|---|
| Recuperação de senha | Link com token válido por 1 hora |
| Atribuição de tarefa | Notifica o responsável designado |
| Novo comentário | Notifica participantes da tarefa |

---

## Funcionalidades

### Gerenciamento de projetos
- Criar workspaces e portfólios para organizar quadros
- Quadros com grupos/colunas personalizáveis
- Visualização **Kanban** (drag & drop nativo), **Lista**, **Tabela** e **Calendário**
- Tarefas com título, descrição, prioridade, prazo, status e responsáveis
- Subtarefas aninhadas
- Etiquetas coloridas
- Dependências entre tarefas
- Campos customizados por quadro

### Colaboração
- Comentários com suporte a threads
- Upload de arquivos (JPG, PNG, GIF, PDF, DOC, XLS, ZIP — até 10 MB)
- Menções e notificações em tempo real (WebSocket)
- Histórico de atividades por quadro
- Múltiplos responsáveis por tarefa

### Automações (IFTTT)
Motor de regras configurável com:

| Gatilho | Ações disponíveis |
|---|---|
| `status_changed` — status alterado | Notificar usuário |
| `item.created` — tarefa criada | Mover para grupo |
| | Definir prioridade |
| | Definir responsável |
| | Criar nova tarefa |

### Dashboard e relatórios
- Visão geral: quadros ativos, tarefas abertas, concluídas e atrasadas
- Tarefas por prioridade (gráfico de barras)
- Tarefas por membro (tabela de ranking)
- Evolução diária nos últimos 30 dias (gráfico de colunas)
- Export para **CSV** (compatível com Excel, com BOM UTF-8)

### Perfil e segurança
- Edição de nome, e-mail e foto de perfil
- Alteração de senha com validação da senha atual
- Gerenciamento de tokens de API (criar e revogar)

---

## Usuários de demonstração

Após executar o `setup.bat` ou os seeds, os seguintes usuários ficam disponíveis:

| Nome | E-mail | Senha | Papel |
|---|---|---|---|
| Administrador | `admin@workday.app` | `password` | admin |
| João Silva | `joao@workday.app` | `password` | member |
| Ana Costa | `ana@workday.app` | `password` | member |

---

## Segurança

- Senhas com **bcrypt** (custo 12)
- Proteção **CSRF** em todos os formulários e requisições fetch
- Headers de segurança: `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `Referrer-Policy`
- Sessão com cookie `HttpOnly`, `SameSite=Lax` e `Secure` (em HTTPS)
- Consultas com **PDO prepared statements** (sem SQL injection)
- Upload com validação de extensão e tamanho
- Tokens de API com hash SHA-256 (o valor bruto nunca é armazenado)
- `APP_DEBUG = false` em produção oculta stack traces

> **Importante:** Antes de colocar em produção, altere o `SECRET_KEY` em `config/config.php` para uma string aleatória e única.
