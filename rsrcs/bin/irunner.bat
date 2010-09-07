@echo off
:start

call php -r "include 'yks/cltools/irunner.php';" %*

if NOT "%ERRORLEVEL%" == "0" goto :error
goto :end

:error
echo Script failure
pause
cls
goto :start

:end