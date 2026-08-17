import { useState } from 'react';

export default function App() {
    const [count, setCount] = useState(0);

    return (
        <div className="p-8">
            <h1 className="text-3xl font-bold">It works!</h1>
            <button className="mt-4 px-4 py-2 bg-blue-600 text-white rounded" onClick={() => setCount(count + 1)}>
                Count: {count}
            </button>
        </div>
    );
}
