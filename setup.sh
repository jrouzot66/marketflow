#!/bin/bash

# Couleurs pour les messages
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║     🚀 MarketFlow Docker Setup           ║${NC}"
echo -e "${BLUE}╔════════════════════════════════════════════╗${NC}"
echo ""

# Vérifier si .env existe
if [ ! -f .env ]; then
    echo -e "${YELLOW}⚠️  Fichier .env non trouvé${NC}"
    echo -e "${GREEN}✓ Création du fichier .env depuis .env.example${NC}"
    cp .env.example .env
else
    echo -e "${GREEN}✓ Fichier .env détecté${NC}"
fi

echo ""
echo -e "${BLUE}📦 Services disponibles :${NC}"
echo -e "  🌐 Web (Nginx)         → http://localhost:8080"
echo -e "  🌐 Healthcheck         → http://localhost:8080/healthz"
echo -e "  🗄️  PostgreSQL         → http://localhost:5432"
echo -e "  ⚡ Redis               → http://localhost:6379"
echo -e "  🐰 RabbitMQ (AMQP)     → http://localhost:5672"
echo -e "  🐰 RabbitMQ (UI)       → http://localhost:15672"
echo -e "  🔍 Elasticsearch       → http://localhost:9200"
echo -e "  📧 Mailpit (SMTP)      → http://localhost:1025"
echo -e "  📧 Mailpit (UI)        → http://localhost:8025"
echo ""

read -p "Voulez-vous démarrer les services maintenant ? (y/n) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${BLUE}🚀 Démarrage des services...${NC}"
    sudo docker-compose up -d

    echo ""
    echo -e "${GREEN}✓ Services démarrés !${NC}"
    echo ""
    echo -e "${BLUE}📊 État des services :${NC}"
    sudo docker-compose ps

    echo ""
    echo -e "${YELLOW}💡 Commandes utiles :${NC}"
    echo -e "  • Voir les logs        : ${GREEN}sudo docker-compose logs -f${NC}"
    echo -e "  • Arrêter les services : ${GREEN}sudo docker-compose down${NC}"
    echo -e "  • Entrer dans PHP      : ${GREEN}sudo docker-compose exec app bash${NC}"
    echo ""
else
    echo -e "${YELLOW}Démarrage annulé. Lancez 'sudo docker-compose up -d' quand vous êtes prêt.${NC}"
fi
