const {AccessPageBO} = require('../../../selectors/BO/access_page');
const {AccessPageFO} = require('../../../selectors/FO/access_page');
const {OnBoarding} = require('../../../selectors/BO/onboarding');
const {CatalogPage} = require('../../../selectors/BO/catalogpage/index');
const {AttributeSubMenu} = require('../../../selectors/BO/catalogpage/attribute_submenu');
const {AddProductPage} = require('../../../selectors/BO/add_product_page');
const {SearchProductPage} = require('../../../selectors/FO/search_product_page');

scenario('Create "Attribute"', () => {
  scenario('Login in the Back Office', client => {
    test('should open the browser', () => client.open());
    test('should login successfully in the Back Office', () => client.signInBO(AccessPageBO));
  }, 'attribute_and_feature');
  scenario('Create a new "Attribute"', client => {
    test('Should go to "Attributes & Features" page', () => client.goToSubtabMenuPage(CatalogPage.menu_button, AttributeSubMenu.submenu));
    test('should click on "Add new attribute" button', () => client.waitForExistAndClick(AttributeSubMenu.add_new_attribute));
    test('should set the "Name" input', () => client.waitAndSetValue(AttributeSubMenu.name_input, 'attribute' + date_time));
    test('should set the "Public name" input', () => client.waitAndSetValue(AttributeSubMenu.public_name_input, 'attribute' + date_time));
    test('should choose the "Type" of attribute', () => client.waitAndSelectByValue(AttributeSubMenu.type_select, 'radio'));
    test('should click on "Save" button', () => client.waitForExistAndClick(AttributeSubMenu.save_button));
    test('should verify the appearance of the green validation', () => client.checkTextValue(CatalogPage.success_panel, '×\nSuccessful creation.'));
    test('should search for the created attribute', () => client.searchByValue(AttributeSubMenu.search_input, AttributeSubMenu.search_button, 'attribute' + date_time));
    test('should select the created attribute', () => client.waitForExistAndClick(AttributeSubMenu.selected_attribute));
    test('should add value to the created attribute', () => client.addValueToAttribute(AttributeSubMenu));
    test('should verify the appearance of the green validation', () => client.checkTextValue(CatalogPage.success_panel, '×\nSuccessful creation.'));
  }, 'attribute_and_feature');
  scenario('Logout from the Back Office', client => {
    test('should logout successfully from Back Office', () => client.signOutBO());
  }, 'attribute_and_feature');
}, 'attribute_and_feature', true);

scenario('Create "Product"', () => {
  scenario('Login in the Back Office', client => {
    test('should open the browser', () => client.open());
    test('should login successfully in the Back Office', () => client.signInBO(AccessPageBO));
  }, 'attribute_and_feature');
  scenario('Create a new "Product"', client => {
    test('should go to "Product Settings" page', () => client.waitForExistAndClick(AddProductPage.products_subtab));
    test('should click on "New Product" button', () => client.waitForExistAndClick(AddProductPage.new_product_button));
    test('should set the "Name" input', () => client.waitAndSetValue(AddProductPage.product_name_input, 'Attribute' + ' ' + date_time));
    test('should set the "Quantity" input', () => client.setQuantity(AddProductPage.quantity_shortcut_input));
    test('should set the "Price" input', () => client.setPrice(AddProductPage.priceTE_shortcut));
    test('should select the "Product with combination" radio button', () => client.waitForExistAndClick(AddProductPage.variations_type_button));
    test('should go to "Combinations" tab', () => client.waitForExistAndClick(AddProductPage.variations_tab));
    test('should select the variation', () => client.selectVariation(AddProductPage, 'attribute' + date_time));
    test('should click on "Generate" button', () => client.waitForExistAndClick(AddProductPage.variations_generate));
    test('should verify the appearance of the green validation', () => client.checkTextValue(AddProductPage.validation_msg, 'Settings updated.'));
    test('should select all the generated variations', () => client.waitForVisibleAndClick(AddProductPage.var_selected));
    test('should set the "Variations quantity" input', () => client.setVariationsQuantity(AddProductPage, "10"));
    test('should switch the product online', () => client.waitForExistAndClick(AddProductPage.product_online_toggle));
    test('should click on "Save" button', () => client.waitForExistAndClick(AddProductPage.save_product_button));
    test('should verify the appearance of the green validation', () => client.checkTextValue(AddProductPage.validation_msg, 'Settings updated.'));
  }, 'create_product');
  scenario('Logout from the Back Office', client => {
    test('should logout successfully from the Back Office', () => client.signOutBO());
  }, 'attribute_and_feature');
}, 'attribute_and_feature', true);

scenario('Check the attribute creation', () => {
  scenario('Login in the Front Office', client => {
    test('should open the browser', () => client.open());
    test('should login successfully in the Front Office', () => client.signInFO(AccessPageFO));
  }, 'attribute_and_feature');
  scenario('Check that the attribute is well created in the Front Office', client => {
    test('should set the shop language to "English"', () => client.languageChange('english'));
    test('should search for the product', () => client.searchByValue(SearchProductPage.search_input, SearchProductPage.search_button, 'attribute'));
    test('should go to the product page', () => client.waitForExistAndClick(SearchProductPage.product_result_name));
    test('should check that the product attribute name is "'+'attribute' + date_time+'"', () => client.checkTextValue(SearchProductPage.attribut_name, 'attribute' + date_time));
    test('should check that the first attribute value is equal to 10', () => client.checkTextValue(SearchProductPage.attribut_value_1, '10'));
    test('should check that the second attribute value is equal to 20', () => client.checkTextValue(SearchProductPage.attribut_value_2, '20'));
    test('should check that the third attribute value is equal to 30', () => client.checkTextValue(SearchProductPage.attribut_value_3, '30'));
  }, 'attribute_and_feature');
  scenario('Logout from the Front Office', client => {
    test('should logout successfully from the Front Office', () => client.signOutFO(AccessPageFO));
  }, 'attribute_and_feature');
}, 'attribute_and_feature', true);