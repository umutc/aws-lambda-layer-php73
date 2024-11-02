const ArrayStats = require('./lib/ArrayStats');

exports.stats = async (event) => {
    const data = typeof event === 'string' ? JSON.parse(event) : event;
    const response = [];
    
    try {
        for (const point of data.points) {
            const arrayStats = new ArrayStats(point.results);
            const stats = {
                _min: data.min,
                _max: data.max,
                mean: arrayStats.mean(),
                median: arrayStats.median(),
                mode: arrayStats.mode(),
                range: arrayStats.range(),
                standard_deviation: arrayStats.standardDeviation(),
                frequency: arrayStats.frequency(),
                min: arrayStats.min(),
                max: arrayStats.max(),
                maxGraphDataValue: 0,
                graphData: arrayStats.graphData(data.min, data.max)
            };

            let maxGraphDataValue = Math.max(...stats.graphData.map(item => item.value));
            stats.maxGraphDataValue = maxGraphDataValue;

            let x = null;
            if (maxGraphDataValue <= 0.0001) x = 10000;
            else if (maxGraphDataValue <= 0.001) x = 1000;
            else if (maxGraphDataValue <= 0.01) x = 100;
            else if (maxGraphDataValue <= 0.1) x = 10;

            if (x) {
                stats.graphData = stats.graphData.map(item => ({
                    ...item,
                    value: item.value * x
                }));
            }

            delete point.results;
            point.stats = stats;
        }
        
        response.push(...data.points);
    } catch (e) {
        console.error(e);
    }
    
    return response;
};

exports.info = async () => {
    return {
        NODE_VERSION: process.version,
        __dirname: __dirname
    };
};

exports.itemAnalysis = async (event) => {
    const data = typeof event === 'string' ? JSON.parse(event) : event;
    
    // Create matrix
    const matrix = {};
    data.esssr.forEach(id => {
        matrix[id] = {};
        data.question_ids.forEach(qId => {
            matrix[id][qId] = 0;
        });
    });

    // Fill matrix
    data.esssrq.forEach(item => {
        matrix[item.esss_id_row_index][item.question_id] = 1;
    });

    // Convert to array and sort by sum descending
    const matrixArray = Object.entries(matrix).map(([key, value]) => ({
        id: key,
        values: value,
        sum: Object.values(value).reduce((a, b) => a + b, 0)
    })).sort((a, b) => b.sum - a.sum);

    // Get top and bottom 27%
    const count = Math.floor(matrixArray.length * 0.27);
    const top27 = matrixArray.slice(0, count);
    const bottom27 = matrixArray.slice(-count);

    // Calculate statistics for each question
    const items = data.question_ids.map(qId => {
        const question = data.questions.find(q => q.id === qId);
        const columnTotal = matrixArray.map(row => row.values[qId]);
        const columnTop27 = top27.map(row => row.values[qId]);
        const columnBottom27 = bottom27.map(row => row.values[qId]);
        
        const totalSum = columnTotal.reduce((a, b) => a + b, 0);
        const totalSumPersentage = (totalSum / matrixArray.length) * 100;
        const totalSumTop27 = columnTop27.reduce((a, b) => a + b, 0);
        const totalSumBottom27 = columnBottom27.reduce((a, b) => a + b, 0);
        const countTop27 = top27.length;
        const countBottom27 = bottom27.length;
        
        const Pj = (totalSumTop27 + totalSumBottom27) / (countTop27 + countBottom27);
        const Sj2 = Pj * (1 - Pj);
        const SS = Math.sqrt(Sj2);
        const rjx = (totalSumTop27 - totalSumBottom27) / countTop27;

        const arrayStats = new ArrayStats(columnTotal);

        return {
            question_id: qId,
            question: question,
            data: {
                totalSum: {
                    id: 'totalSum',
                    title: 'Maddeyi toplam doğru cevaplayan öğrenci sayısı',
                    value: totalSum
                },
                totalSumPersentage: {
                    id: 'totalSumPersentage',
                    title: 'Madde başarı yüzdesi',
                    value: totalSumPersentage
                },
                totalSumTop27: {
                    id: 'totalSumTop27',
                    title: 'Maddeyi üst grupta doğru cevaplayan öğrenci sayısı',
                    value: totalSumTop27
                },
                totalSumBottom27: {
                    id: 'totalSumBottom27',
                    title: 'Maddeyi alt grupta doğru cevaplayan öğrenci sayısı',
                    value: totalSumBottom27
                },
                Pj: {
                    id: 'Pj',
                    title: 'Madde güçlük indeksi',
                    value: Pj
                },
                Sj2: {
                    id: 'Sj2',
                    title: 'Madde varyansı',
                    value: Sj2
                },
                rjx: {
                    id: 'rjx',
                    title: 'Madde ayırıcılık gücü',
                    value: rjx
                },
                SS: {
                    id: 'SS',
                    title: 'Standart sapma',
                    value: SS
                },
                ri: {
                    id: 'ri',
                    title: 'Madde güvenirlik indeksi',
                    value: rjx * SS
                },
                stats: {
                    _min: 0,
                    _max: 1,
                    mean: arrayStats.mean(),
                    median: arrayStats.median(),
                    mode: arrayStats.mode(),
                    range: arrayStats.range(),
                    variance: arrayStats.variance(),
                    standard_deviation: arrayStats.standardDeviation(),
                    frequency: arrayStats.frequency(),
                    min: arrayStats.min(),
                    max: arrayStats.max(),
                    maxGraphDataValue: 1,
                    graphData: arrayStats.graphData(0, 1)
                }
            }
        };
    });

    // Calculate graph data categories
    const graphData = [
        {
            id: 1,
            name: '[Pj>0.90]',
            value: 0,
            desc: 'Eğer etkili bir öğretim varsa tercih edilir'
        },
        {
            id: 2,
            name: '[Pj>=0.60][rjx>=0.20]',
            value: 0,
            desc: 'Tipik iyi bir madde'
        },
        {
            id: 3,
            name: '[Pj>=0.60][rjx<0.20]',
            value: 0,
            desc: 'Üzerinde çalışılması gereken madde'
        },
        {
            id: 4,
            name: '[Pj<0.60][rjx>=0.20]',
            value: 0,
            desc: 'Zor fakat ayırt edici bir madde (Eğer yüksek standartlara sahipseniz bu soru iyidir)'
        },
        {
            id: 5,
            name: '[Pj<0.60][rjx<0.20]',
            value: 0,
            desc: 'Zor ve ayırt edici olmayan madde (Bu madde kullanılamaz)'
        }
    ];

    // Calculate Pj values and update graph data
    const Pj = [];
    items.forEach(item => {
        Pj.push(item.data.Pj.value);
        const pjValue = item.data.Pj.value;
        const rjxValue = item.data.rjx.value;

        if (pjValue > 0.90) {
            graphData[0].value++;
        } else if (pjValue >= 0.60 && rjxValue >= 0.20) {
            graphData[1].value++;
        } else if (pjValue >= 0.60 && rjxValue < 0.20) {
            graphData[2].value++;
        } else if (pjValue < 0.60 && rjxValue >= 0.20) {
            graphData[3].value++;
        } else if (pjValue < 0.60 && rjxValue < 0.20) {
            graphData[4].value++;
        }
    });

    return {
        PjAvg: Pj.reduce((a, b) => a + b, 0) / Pj.length,
        studentCount: data.esssr.length,
        questionCount: data.question_ids.length,
        graphData: graphData,
        items: items
    };
};
