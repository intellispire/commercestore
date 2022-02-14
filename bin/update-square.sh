# Remove anything that already exists.
rm -rf includes/gateways/square

# Initial clone
git clone -b main git@github.com:intellispire/cs-square includes/gateways/square

# Install dependencies
cd includes/gateways/square
composer install --no-dev
npm install && npm run build

git rev-parse HEAD > ../.square-hash

# Clean up files for distribution.
# @todo Maybe use git archive? However composer.json would
# need to be removed from .gitattributes export-ignore
rm -rf node_modules
rm -rf tests
rm -rf bin
rm -rf languages
rm -rf includes/pro
rm -rf .git
rm -rf .github
rm .gitattributes
rm .gitignore
rm .npmrc
rm package-lock.json
rm package.json
rm composer.json
rm composer.lock
rm webpack.config.js
rm phpcs.ruleset.xml
rm phpunit.xml.dist

# Reset cwd
cd ../../../
