.PHONY: run serve up npm dev

APP_NAME := mallas_arica

up:
	docker compose up -d
	@echo "Dev container up and running in background" 
	
serve:
	@echo "PHP artisan server started"
	php artisan serve
	

npm:
	@echo "Styles compiled" && npm run dev

dev:
	@echo "Starting the ${APP_NAME} for development session..."
	up
	serve

