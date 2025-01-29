// tests/js/exchange-rate-widget.test.js
import { ExchangeRateWidget } from '../../public/js/exchange-rate-widget.js';

describe('ExchangeRateWidget', () => {
    let container;
    let widget;

    beforeEach(() => {
        // Set up a mock DOM element
        container = document.createElement('div');
        container.id = 'exchange-rates';
        document.body.appendChild(container);

        // Mock successful fetch response
        global.fetch = jest.fn().mockResolvedValue({
            json: () => Promise.resolve([
                {
                    code: 'USD',
                    rate: 75.5,
                    trend: 'up',
                    change: 0.5
                },
                {
                    code: 'EUR',
                    rate: 85.3,
                    trend: 'down',
                    change: -0.3
                }
            ])
        });

        // Initialize widget
        widget = new ExchangeRateWidget('exchange-rates');
    });

    afterEach(() => {
        document.body.removeChild(container);
        global.fetch.mockClear();
    });

    test('widget initializes correctly', async () => {
        await new Promise(resolve => setTimeout(resolve, 0));
        const ratesContainer = container.querySelector('.rates-container');
        expect(ratesContainer).toBeTruthy();
        const rateItems = ratesContainer.querySelectorAll('.rate-item');
        expect(rateItems.length).toBe(2);
    });

    test('creates rate items with correct data', async () => {
        await new Promise(resolve => setTimeout(resolve, 0));
        const rateItems = container.querySelectorAll('.rate-item');

        // Check first rate item (USD)
        const usdRateItem = rateItems[0];
        expect(usdRateItem.querySelector('.currency-code').textContent).toBe('USD');
        expect(usdRateItem.querySelector('.rate-value').textContent).toContain('75.5 ₽ ↑');
        expect(usdRateItem.querySelector('.change').textContent).toBe('(0.5%)');

        // Check second rate item (EUR)
        const eurRateItem = rateItems[1];
        expect(eurRateItem.querySelector('.currency-code').textContent).toBe('EUR');
        expect(eurRateItem.querySelector('.rate-value').textContent).toContain('85.3 ₽ ↓');
        expect(eurRateItem.querySelector('.change').textContent).toBe('(-0.3%)');
    });

    test('updates last update time', async () => {
        await new Promise(resolve => setTimeout(resolve, 0));
        const lastUpdateElement = container.querySelector('.last-update');
        expect(lastUpdateElement.textContent).toMatch(/Последнее обновление: \d{1,2}:\d{2}:\d{2}/);
    });
});