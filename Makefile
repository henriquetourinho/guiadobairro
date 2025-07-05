app-up:
	docker compose up -d

docker-prune-without-volumes:
	docker system prune -a --volumes -f
	docker volume rm `docker volume ls`