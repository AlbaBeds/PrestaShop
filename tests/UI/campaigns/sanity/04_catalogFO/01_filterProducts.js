// Import utils
import testContext from '@utils/testContext';
import helper from '@utils/helpers';

require('module-alias/register');
// Using chai
const {expect} = require('chai');

// Import pages
const homePage = require('@pages/FO/home');

// Import data
const {Categories} = require('@data/demo/categories');

const baseContext = 'sanity_catalogFO_filterProducts';

let browserContext;
let page;
let allProductsNumber = 0;

/*
  Open the FO home page
  Get the product number
  Filter products by a category
  Filter products by a subcategory
 */
describe('FO - Catalog : Filter Products by categories in Home page', async () => {
  // before and after functions
  before(async function () {
    browserContext = await helper.createBrowserContext(this.browser);
    page = await helper.newTab(browserContext);
  });

  after(async () => {
    await helper.closeBrowserContext(browserContext);
  });

  // Steps
  it('should open the shop page', async function () {
    await testContext.addContextItem(this, 'testIdentifier', 'goToShopFO', baseContext);

    await homePage.goTo(page, global.FO.URL);
    const result = await homePage.isHomePage(page);
    await expect(result).to.be.true;
  });

  it('should check and get the products number', async function () {
    await testContext.addContextItem(this, 'testIdentifier', 'checkNumberOfProducts', baseContext);

    await homePage.waitForSelectorAndClick(page, homePage.allProductLink);
    allProductsNumber = await homePage.getNumberFromText(page, homePage.totalProducts);
    await expect(allProductsNumber).to.be.above(0);
  });

  it('should filter products by the category \'Accessories\' and check result', async function () {
    await testContext.addContextItem(this, 'testIdentifier', 'FilterProductByCategory', baseContext);

    await homePage.goToCategory(page, Categories.accessories.id);
    const numberOfProducts = await homePage.getNumberFromText(page, homePage.totalProducts);
    await expect(numberOfProducts).to.be.below(allProductsNumber);
  });

  it('should filter products by the subcategory \'Stationery\' and check result', async function () {
    await testContext.addContextItem(this, 'testIdentifier', 'FilterProductBySubCategory', baseContext);

    await homePage.goToSubCategory(page, Categories.accessories.id, Categories.stationery.id);
    const numberOfProducts = await homePage.getNumberFromText(page, homePage.totalProducts);
    await expect(numberOfProducts).to.be.below(allProductsNumber);
  });
});
