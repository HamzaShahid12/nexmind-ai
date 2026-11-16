@echo off
REM One-click setup for Windows/Laragon. Run from this folder in a terminal where php, composer and mysql work.
call composer install || goto :err
if not exist .env copy .env.example .env
call php artisan key:generate || goto :err
echo.
echo Edit .env now (DB_DATABASE, DB_USERNAME, DB_PASSWORD, OPENAI_API_KEY, SEED_ADMIN_PASSWORD, SEED_DEMO_PASSWORD)
echo and create the empty MySQL database. Then press any key to migrate.
pause
call php artisan migrate --seed || goto :err
call php artisan storage:link
echo.
echo Done. Start with:  php artisan serve   then open http://localhost:8000
goto :eof
:err
echo Setup failed - see the error above.
exit /b 1
