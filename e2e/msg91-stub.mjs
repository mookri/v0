import http from 'node:http';

const PORT = parseInt(process.env.MSG91_STUB_PORT || '9101', 10);

const server = http.createServer((req, res) => {
  const url = req.url || '';
  if (url === '/health') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ status: 'ok' }));
    return;
  }

  if (url.startsWith('/api/v5/widget/verifyAccessToken') && req.method === 'POST') {
    let body = '';
    req.on('data', (chunk) => {
      body += chunk;
    });
    req.on('end', () => {
      try {
        const parsed = JSON.parse(body || '{}');
        const token = parsed.token || '';

        if (token === 'invalid-token' || token === 'expired-token' || !token) {
          res.writeHead(200, { 'Content-Type': 'application/json' });
          res.end(
            JSON.stringify({
              status: '400',
              data: {
                type: 'error',
                message: 'Invalid access token',
              },
            }),
          );
          return;
        }

        // Return attested identifier (defaults to E2E test phone 919877001122)
        const phone = process.env.MSG91_STUB_ATTESTED_PHONE || '919877001122';
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(
          JSON.stringify({
            status: '200',
            data: {
              type: 'success',
              message: phone,
            },
          }),
        );
      } catch (e) {
        res.writeHead(400, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ error: 'Malformed JSON' }));
      }
    });
    return;
  }

  res.writeHead(404, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify({ error: 'Not found' }));
});

server.listen(PORT, '127.0.0.1', () => {
  console.log(`MSG91 verification stub listening on http://127.0.0.1:${PORT}`);
});
