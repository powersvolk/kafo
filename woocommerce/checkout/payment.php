<?php

echo gotoAndPlay\Template::compileComponent('@view-checkout--payments', \gotoAndPlay\Templates\Checkout::getPaymentmethods($available_gateways));
