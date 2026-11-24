#!/bin/bash
set -euo pipefail

PATCHES_URL="https://raw.githubusercontent.com/LokiCheckout/magento-2.4.5-patches/3848e73173a7400a5687f323804da8bc611a7231/modules/patches.json"
PATCHES_SHA256="73beff632f7664208b3de7b4b14929773f669394a709adfd0356c88bf86e4ded"

cp composer.json composer-old.json
curl -fsSL "$PATCHES_URL" -o loki-checkout-patches.json
echo "$PATCHES_SHA256  loki-checkout-patches.json" | sha256sum -c -
jq -s '.[0] + {extra: ((.[0].extra // {}) + {patches: .[1].extra.patches})}' composer-old.json loki-checkout-patches.json > composer.json
