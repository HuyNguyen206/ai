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

# MCP Inspector must bind 0.0.0.0 to be reachable from the host: its default
# loopback bind only listens inside the container. The compose file publishes
# 6274/6275/6277/6278 on the host's 127.0.0.1, so the wide bind stays off the LAN.
# Skipped when it is already up: a second instance dies on "PORT IS IN USE" and
# would overwrite the log holding the running instance's auth token.
inspector:
	@if docker compose -f ../../server/docker-compose.yml exec -T -u=dev ai \
		curl -sf -o /dev/null --max-time 2 http://localhost:6274/ 2>/dev/null; then \
		echo "inspector: already running"; \
	else \
		docker compose -f ../../server/docker-compose.yml exec -d -u=dev ai \
			sh -lc 'HOST=0.0.0.0 DANGEROUSLY_BIND_ALL_INTERFACES=true npx -y @modelcontextprotocol/inspector > storage/logs/inspector.log 2>&1'; \
		sleep 15; \
	fi
	@docker compose -f ../../server/docker-compose.yml exec -T -u=dev ai \
		sh -lc 'grep -m1 "MCP_INSPECTOR_API_TOKEN" storage/logs/inspector.log' \
		|| echo "inspector: no URL in storage/logs/inspector.log yet - check it for errors"

inspector-stop:
	@docker compose -f ../../server/docker-compose.yml exec -T -u=dev ai \
		sh -lc 'pkill -f "modelcontextprotocol/inspector" || true'
	@echo "inspector: stopped"
