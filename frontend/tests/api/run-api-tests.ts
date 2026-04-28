import { printResults } from './helpers';

import { runAuthTests } from './auth.test';
import { runCmsTests } from './cms.test';
import { runProductsTests } from './products.test';
import { runCategoriesTests } from './categories.test';
import { runBrandsTests } from './brands.test';
import { runCartTests } from './cart.test';
import { runWishlistTests } from './wishlist.test';
import { runAddressesTests } from './addresses.test';
import { runOrdersTests } from './orders.test';
import { runOffersTests } from './offers.test';
import { runReturnsTests } from './returns.test';
import { runNotificationsTests } from './notifications.test';
import { runSupportTests } from './support.test';
import { runWalletTests } from './wallet.test';

async function main() {
  console.log('\n\x1b[1m\x1b[35m╔══════════════════════════════════════╗\x1b[0m');
  console.log('\x1b[1m\x1b[35m║   B2B Pharmacy API Test Suite        ║\x1b[0m');
  console.log('\x1b[1m\x1b[35m╚══════════════════════════════════════╝\x1b[0m');

  await runAuthTests();
  await runCmsTests();
  await runProductsTests();
  await runCategoriesTests();
  await runBrandsTests();
  await runCartTests();
  await runWishlistTests();
  await runAddressesTests();
  await runOrdersTests();
  await runOffersTests();
  await runReturnsTests();
  await runNotificationsTests();
  await runSupportTests();
  await runWalletTests();

  printResults();
}

main().catch((err) => {
  console.error('Fatal error:', err);
  process.exit(1);
});
