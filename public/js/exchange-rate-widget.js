export class ExchangeRateWidget {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        const config = window.exchangeRatesConfig || {};

        this.options = {
            updateInterval: options.updateInterval || config.widgetUpdateInterval || 300000,
            apiUrl: options.apiUrl || '/api/rates/current',
            displayCurrencies: options.displayCurrencies || config.displayCurrencies || ['USD', 'EUR', 'CNY', 'KRW', 'JPY']
        };

        this.init();
    }

    async init() {
        this.createWidget();
        await this.updateRates();
        this.startAutoUpdate();
    }

    createWidget() {
        this.container.innerHTML = `
            <div class="exchange-rate-widget">
                <h3>Курсы валют ЦБ РФ</h3>
                <div class="rates-container"></div>
                <div class="last-update"></div>
            </div>
        `;

        const style = document.createElement('style');
        style.textContent = `
            .exchange-rate-widget {
                font-family: Arial, sans-serif;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .rate-item {
                display: flex;
                justify-content: space-between;
                padding: 10px 0;
                border-bottom: 1px solid #eee;
            }
            .trend-up {
                color: #4CAF50;
            }
            .trend-down {
                color: #F44336;
            }
            .last-update {
                font-size: 12px;
                color: #666;
                margin-top: 10px;
            }
        `;
        document.head.appendChild(style);
    }

    async updateRates() {
        try {
            const response = await fetch(this.options.apiUrl);
            const rates = await response.json();

            console.log('Received rates:', rates);
            console.log('Display currencies:', this.options.displayCurrencies);

            const filteredRates = rates.filter(rate =>
                this.options.displayCurrencies.includes(rate.code)
            );

            console.log('Filtered rates:', filteredRates);

            const container = this.container.querySelector('.rates-container');
            container.innerHTML = filteredRates.map(rate =>
                this.createRateItem(rate)
            ).join('');

            this.updateLastUpdateTime();
        } catch (error) {
            console.error('Failed to update rates:', error);
        }
    }

    createRateItem(rate) {
        const trendClass = rate.trend === 'up' ? 'trend-up' :
            rate.trend === 'down' ? 'trend-down' : '';
        const trendArrow = rate.trend === 'up' ? '↑' :
            rate.trend === 'down' ? '↓' : '';

        return `
            <div class="rate-item">
                <span class="currency-code">${rate.code}</span>
                <span class="rate-value ${trendClass}">
                    ${rate.rate} ₽ ${trendArrow}
                    <span class="change">(${rate.change}%)</span>
                </span>
            </div>
        `;
    }

    updateLastUpdateTime() {
        const lastUpdate = this.container.querySelector('.last-update');
        lastUpdate.textContent = `Последнее обновление: ${new Date().toLocaleTimeString()}`;
    }

    startAutoUpdate() {
        setInterval(() => this.updateRates(), this.options.updateInterval);
    }
}
