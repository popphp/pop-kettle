import { useState } from 'react';

export default function App() {
    const [count, setCount] = useState(0);

    return (
        <div className="inline-flex items-center gap-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-5 py-3">
            <span className="text-sm text-slate-500 dark:text-slate-400">React is working</span>
            <button
                type="button"
                className="px-3 py-1.5 text-sm font-semibold text-white bg-orange-700 rounded-md hover:bg-orange-800 transition"
                onClick={() => setCount(count + 1)}
            >
                Count: {count}
            </button>
        </div>
    );
}
