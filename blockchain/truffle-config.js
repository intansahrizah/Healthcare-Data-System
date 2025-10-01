// blockchain/truffle-config.js
/**
 * Truffle configuration for HealthcareSystem blockchain development
 */

module.exports = {
  /**
   * Networks configuration for connecting to Ethereum clients
   */
  networks: {
    // Development network for Ganache
    development: {
      host: "127.0.0.1",     // Localhost
      port: 8545,            // Ganache GUI port (8545) or CLI port (7545)
      network_id: "*",       // Match any network ID
      gas: 6721975,          // Gas limit
      gasPrice: 20000000000, // 20 gwei
    },
    
    // Alternative Ganache CLI configuration
    ganache: {
      host: "127.0.0.1",
      port: 7545,            // Ganache CLI default port
      network_id: "*",
      gas: 6721975,
      gasPrice: 20000000000,
    },

    // You can uncomment and configure these for other networks later
    /*
    goerli: {
      provider: () => new HDWalletProvider(MNEMONIC, `https://goerli.infura.io/v3/${PROJECT_ID}`),
      network_id: 5,       // Goerli's id
      confirmations: 2,    // # of confirmations to wait between deployments
      timeoutBlocks: 200,  // # of blocks before a deployment times out
      skipDryRun: true     // Skip dry run before migrations
    },
    */
  },

  // Set default mocha options here, use special reporters, etc.
  mocha: {
    timeout: 40000 // Increased timeout for slower networks
  },

  // Configure your compilers
  compilers: {
    solc: {
      version: "0.8.19",      // Use Solidity version that matches your contracts
      settings: {          // Optimization settings for better gas usage
        optimizer: {
          enabled: true,
          runs: 200
        },
        evmVersion: "london" // EVM version to target
      }
    }
  },

  // Truffle DB is currently disabled by default; to enable it, change enabled:
  // false to enabled: true. The default storage location can also be
  // overridden by specifying the adapter settings, as shown in the commented code below.
  //
  // NOTE: It is not possible to migrate your contracts to truffle DB and you should
  // make a backup of your artifacts to a safe location before enabling this feature.
  //
  // After you backed up your artifacts you can utilize db by running migrate as follows:
  // $ truffle migrate --reset --compile-all
  //
  // db: {
  //   enabled: false,
  //   host: "127.0.0.1",
  //   adapter: {
  //     name: "indexeddb",
  //     settings: {
  //       directory: ".db"
  //     }
  //   }
  // }
};