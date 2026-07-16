# Serviços Sync (Emissor)

Instalar na **Plataforma de Serviços** (o WordPress onde gere as encomendas de personalização).

## Instalação

1. Carregue a pasta `servicos-sync` para `wp-content/plugins/`.
2. Ative o plugin em Plugins > Serviços Sync.
3. Vá a **Serviços Sync** no menu lateral do wp-admin.

## Configuração da ligação ao E-commerce

Antes de configurar aqui, tem de preparar o lado do E-commerce:

1. No **E-commerce**, instale e ative o plugin `servicos-sync-conta`.
2. No E-commerce, vá a **Utilizadores > Adicionar novo** e crie um utilizador dedicado:
   - Nome de utilizador sugerido: `sync-servico`
   - Role: **"Serviços Sync (utilizador de serviço)"** (criada automaticamente pelo plugin)
   - Este utilizador não deve ter password fraca nem ser usado para login normal.
3. Ainda no E-commerce, aceda ao perfil desse utilizador → secção **Application Passwords** → gere uma nova password com o nome "Sincronização Serviços".
4. Copie a Application Password gerada (só é mostrada uma vez).

Volte à **Plataforma de Serviços** e preencha em Serviços Sync > Configuração:

- **URL do E-commerce**: ex. `https://www.suamarca.com`
- **Utilizador de serviço**: `sync-servico`
- **Application Password**: a password copiada no passo anterior

Clique em "Testar ligação" para confirmar que está tudo correto.

## Como funciona

- Sempre que uma encomenda muda de status (ou é gravada com um novo valor), os dados são enviados automaticamente para o E-commerce.
- Se o envio falhar (rede em baixo, etc.), o sistema tenta novamente automaticamente (5min, 15min, 1h, até 5 tentativas).
- Todas as noites às 03:00, uma reconciliação completa compara todas as encomendas dos últimos 90 dias com o E-commerce e corrige qualquer divergência.
- Se uma encomenda continuar por sincronizar após esgotar as tentativas, é enviado um email de alerta para o email de administrador do WordPress.
- O ecrã "Serviços Sync" mostra o histórico de sincronização e permite reenviar manualmente qualquer registo falhado.

## Notas importantes

- Os campos sincronizados são: referência da encomenda, email do cliente, valor, moeda e status. **Ajuste o método `sincronizar_encomenda()`** em `includes/class-servicos-sync-sender.php` se precisar de enviar campos adicionais (ex: descrição da peça).
- A correspondência de cliente é feita por **email**. Se o cliente não tiver ainda conta no E-commerce com esse email, a encomenda fica em log mas não aparece na área do cliente — assim que a conta existir, a próxima reconciliação (até 24h) associa-a automaticamente. Se preferir associação imediata, pode reduzir o intervalo do cron de reconciliação.
