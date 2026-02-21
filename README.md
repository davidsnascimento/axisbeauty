# AxisBeauty
Gestão Inteligente para seu Salão

## Versão
v1.0 + Autenticação Hardcore

## Rodar local (XAMPP)
1. Crie o banco `axisbeauty`
2. Rode `database/auth_hardcore_v1.sql`
3. Ajuste `config/config.php`
4. Acesse:
   - /axisbeauty/public/register.php
   - /axisbeauty/public/login.php
   - /axisbeauty/public/dashboard.php

## Segurança
- CSRF
- Sessão server-side (user_sessions)
- Bloqueio por tentativas
- Remember-me seguro (selector+validator)
- Reset de senha
- MFA TOTP (Google/Microsoft Authenticator)


## Compatibilidade MariaDB
- `audit_logs.detalhes` usa LONGTEXT para compatibilidade com versões que não suportam CAST para JSON.


## RBAC + Multi-Conta
- Rode também: `database/rbac_multicontas_v1.sql`


## Verificação de Email (Cadastro Público)
- Rode também: `database/email_verification_v1.sql`
- Em DEV, o link aparece na tela. Em produção, enviar por email.
