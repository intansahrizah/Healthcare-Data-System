/**
 * Truffle configuration for HealthcareSystem Blockchain
 */
module.exports = {
  /**
   * Networks configuration for connecting to Ethereum clients
   */
  networks: {
    // Development network for Ganache
    development: {
      host: "127.0.0.1",     // Localhost
      port: 7545,            // Ganache CLI port
      network_id: "*",       // Match any network id
      gas: 6721975,          // Gas limit
      gasPrice: 20000000000, // 20 gwei
    },

    // Additional network for Ganache GUI (if using)
    ganache: {
      host: "127.0.0.1",
      port: 7545,
      network_id: "*",
    },

    // You can add other networks here when ready:
    /*
    goerli: {
      provider: () => new HDWalletProvider(process.env.MNEMONIC, `https://goerli.infura.io/v3/${process.env.PROJECT_ID}`),
      network_id: 5,
      gas: 5500000,
      confirmations: 2,
      timeoutBlocks: 200,
      skipDryRun: true
    },
    mainnet: {
      provider: () => new HDWalletProvider(process.env.MNEMONIC, `https://mainnet.infura.io/v3/${process.env.PROJECT_ID}`),
      network_id: 1,
      gas: 5500000,
      gasPrice: 20000000000, // 20 gwei
      confirmations: 2,
      timeoutBlocks: 200,
      skipDryRun: false
    }
    */
  },

  // Set default mocha options here, use special reporters, etc.
  mocha: {
    timeout: 100000 // 100 seconds - for slow RPC calls
  },

  // Configure your compilers
  compilers: {
    solc: {
      version: "0.8.21",      // Match your contract pragma version
      docker: false,          // Set to true to use a dockerized solc
      settings: {             // Optimizer settings
        optimizer: {
          enabled: true,      // Enable optimizer for gas efficiency
          runs: 200           // Optimize for how many times you expect to run the code
        },
        evmVersion: "istanbul" // EVM version to target
      }
    }
  },

  // Truffle DB configuration (optional)
  db: {
    enabled: false
    // host: "127.0.0.1",
    // adapter: {
    //   name: "indexeddb",
    //   settings: {
    //     directory: ".db"
    //   }
    // }
  }

  // Plugins configuration (optional)
  // plugins: ["truffle-plugin-verify"]
};