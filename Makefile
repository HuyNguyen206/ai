.PHONY: *

infra-shell-php:
	docker compose -f ../../server/docker-compose.yml exec -u=dev -it ai bash -l

infra-shell-root-php:
	docker compose -f ../../server/docker-compose.yml exec -it ai bash -l

start:
	docker compose -f ../../server/docker-compose.yml up -d
	@#$(MAKE) --no-print-directory vite

# Vite must run inside the container, not on the host: the browser tests reach
# the dev server at the URL in public/hot, and a host-bound vite is unreachable
# from there. Skipped when it is already up, because a second instance dies on
# strictPort and deletes public/hot on its way out, breaking assets for the
# instance that is still running.
vite:
	@if docker compose -f ../../server/docker-compose.yml exec -T -u=dev ai \
		curl -sf -o /dev/null --max-time 2 http://localhost:5173/@vite/client 2>/dev/null; then \
		echo "vite: already running"; \
	else \
		docker compose -f ../../server/docker-compose.yml exec -d -u=dev ai \
			sh -lc 'npm run dev > storage/logs/vite.log 2>&1'; \
		echo "vite: started (logs: storage/logs/vite.log)"; \
	fi
