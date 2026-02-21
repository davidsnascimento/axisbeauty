# 03 - Segurança

## Autenticação
- Hash seguro de senha
- Controle de tentativas
- Sessão segura
- MFA preparado

## RBAC
Roles fixas:
- super_admin
- dono_conta
- gerente
- recepcao
- profissional
- financeiro

Permissões por chave:
dashboard.ver
usuarios.gerenciar
clientes.criar
agenda.editar
financeiro.exportar

## Verificação de Email
- Token hash SHA-256
- Expiração 24h
- Status pendente até confirmação
- Anti enumeração