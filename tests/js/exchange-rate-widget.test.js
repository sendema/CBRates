import { ExchangeRateWidget } from '../../public/js/exchange-rate-widget.js';

describe('ExchangeRateWidget', () => {
    let container;
    let widget;

    beforeEach(() => {
        container = document.createElement('div');
        container.id = 'exchange-rates';
        document.body.appendChild(container);

        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: () => Promise.resolve([
                {
                    code: 'USD',
                    rate: 75.5,
                    trend: 'up',
                    change: 0.5,
                    value: '75.5',
                    nominal: '1',
                    previousValue: '75.0'
                },
                {
                    code: 'EUR',
                    rate: 85.3,
                    trend: 'down',
                    change: -0.3,
                    value: '85.3',
                    nominal: '1',
                    previousValue: '85.6'
                }
            ])
        });

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

        const usdRateItem = rateItems[0];
        expect(usdRateItem.querySelector('.currency-code').textContent).toBe('USD');
        expect(usdRateItem.querySelector('.rate-value').textContent).toContain('75.5 ₽ ↑');
        expect(usdRateItem.querySelector('.change').textContent).toBe('(0.5%)');

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