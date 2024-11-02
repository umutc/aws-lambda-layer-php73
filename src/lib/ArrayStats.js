class ArrayStats {
    constructor(array) {
        if (!array || array.length === 0) {
            throw new Error("Array cannot be empty");
        }
        this.array = array.map(Number).sort((a, b) => b - a);
    }

    mean() {
        return this.array.reduce((a, b) => a + b, 0) / this.array.length;
    }

    median() {
        const mid = Math.floor(this.array.length / 2);
        return this.array.length % 2 !== 0 
            ? this.array[mid] 
            : (this.array[mid - 1] + this.array[mid]) / 2;
    }

    mode() {
        const frequency = {};
        let maxFreq = 0;
        let mode = null;

        this.array.forEach(num => {
            frequency[num] = (frequency[num] || 0) + 1;
            if (frequency[num] > maxFreq) {
                maxFreq = frequency[num];
                mode = num;
            }
        });

        return mode;
    }

    range() {
        return Math.max(...this.array) - Math.min(...this.array);
    }

    standardDeviation() {
        const mean = this.mean();
        const squareDiffs = this.array.map(value => {
            const diff = value - mean;
            return diff * diff;
        });
        const avgSquareDiff = squareDiffs.reduce((a, b) => a + b, 0) / this.array.length;
        return Math.sqrt(avgSquareDiff);
    }

    frequency() {
        const freq = {};
        this.array.forEach(num => {
            freq[num] = (freq[num] || 0) + 1;
        });
        return freq;
    }

    min() {
        return Math.min(...this.array);
    }

    max() {
        return Math.max(...this.array);
    }

    graphData(min = 0, max = 100) {
        const mean = this.mean();
        const stdDev = this.standardDeviation();
        const data = [];

        for (let i = min; i <= max; i++) {
            data.push({
                name: i.toString(),
                value: this.densNormal(i, mean, stdDev)
            });
        }

        return data;
    }

    densNormal(x, mean, stdDev) {
        if (stdDev <= 0) return 0;
        const exp = Math.exp(-(Math.pow(x - mean, 2) / (2 * Math.pow(stdDev, 2))));
        return (1 / (stdDev * Math.sqrt(2 * Math.PI))) * exp;
    }

    variance() {
        const mean = this.mean();
        const squareDiffs = this.array.map(value => {
            const diff = value - mean;
            return diff * diff;
        });
        return squareDiffs.reduce((a, b) => a + b, 0) / this.array.length;
    }
}

module.exports = ArrayStats;
